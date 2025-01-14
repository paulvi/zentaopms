<?php
/**
 * The model file of action module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     action
 * @version     $Id: model.php 5028 2013-07-06 02:59:41Z wyd621@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php
class actionModel extends model
{
    const BE_UNDELETED  = 0;    // The deleted object has been undeleted.
    const CAN_UNDELETED = 1;    // The deleted object can be undeleted.
    const BE_HIDDEN     = 2;    // The deleted object has been hidded.

    /**
     * Create a action.
     * 
     * @param  string $objectType 
     * @param  int    $objectID 
     * @param  string $actionType 
     * @param  string $comment 
     * @param  string $extra        the extra info of this action, according to different modules and actions, can set different extra.
     * @param  string $actor
     * @param  bool   $autoDelete
     * @access public
     * @return int
     */
    public function create($objectType, $objectID, $actionType, $comment = '', $extra = '', $actor = '', $autoDelete = true)
    {
        if(strtolower($actionType) == 'commented' and empty($comment)) return false;
        $actor      = $actor ? $actor : $this->app->user->account;
        $actionType = strtolower($actionType);
        if($actor == 'guest' and $actionType == 'logout') return false;
        
        $action = new stdclass();

        $objectType = str_replace('`', '', $objectType);
        $action->objectType = strtolower($objectType);
        $action->objectID   = $objectID;
        $action->actor      = $actor;
        $action->action     = $actionType;
        $action->date       = helper::now();
        $action->extra      = $extra;

        /* Use purifier to process comment. Fix bug #2683. */
        $action->comment  = fixer::dataStripTags($comment);

        /* Process action. */
        $action = $this->loadModel('file')->processImgURL($action, 'comment', $this->post->uid);
        if($autoDelete) $this->file->autoDelete($this->post->uid);

        /* Get product and project for this object. */
        $productAndProject = $this->getProductAndProject($action->objectType, $objectID);
        $action->product   = $productAndProject['product'];
        $action->project   = (int) $productAndProject['project'];

        $this->dao->insert(TABLE_ACTION)->data($action)->autoCheck()->exec();
        $actionID = $this->dbh->lastInsertID();

        $this->file->updateObjectID($this->post->uid, $objectID, $objectType);

        $this->loadModel('message')->send($objectType, $objectID, $actionType, $actionID);

        return $actionID;
    }

    /**
     * Update read field of action when view a task/bug.
     * 
     * @param  string $objectType 
     * @param  int    $objectID 
     * @access public
     * @return void
     */
    public function read($objectType, $objectID)
    {
        $this->dao->update(TABLE_ACTION)
            ->set('`read`')->eq(1)
            ->where('objectType')->eq($objectType)
            ->andWhere('objectID')->eq($objectID)
            ->andWhere('`read`')->eq(0)
            ->exec();
    }

    /**
     * Get the unread actions.
     * 
     * @param  int    $actionID 
     * @access public
     * @return void
     */
    public function getUnreadActions($actionID = 0)
    {
        if(!is_numeric($actionID)) $actionID = 0;
        $objectList['task'] = TABLE_TASK;
        $objectList['bug']  = TABLE_BUG;
        $actions = array();

        foreach($objectList as $object => $table)
        {
            $idList = $this->dao->select('id')->from($table)->where('assignedTo')->eq($this->app->user->account)->fetchPairs('id');

            $tmpActions = $this->dao->select('*')->from(TABLE_ACTION)
                ->where('objectType')->eq($object)
                ->andWhere('objectID')->in($idList)
                ->andWhere('`read`')->eq(0)
                ->andWhere('id')->gt($actionID)
                ->fetchAll('id');

            if(empty($tmpActions)) continue;

            $tmpActions = $this->transformActions($tmpActions);
            foreach($tmpActions as $action)
            {
                $actions[$action->objectType][] = array(
                    'actionID'   => $action->id,
                    'objectType' => $action->objectType,
                    'objectID'   => $action->objectID,
                    'action'     => $action->actor . ' ' . $action->actionLabel . ' ' . $action->objectType . " #$action->objectID" . $action->objectName
                );
            }
        }
        return json_encode($actions);
    }

    /**
     * Get product and project of an object.
     * 
     * @param  string $objectType 
     * @param  int    $objectID 
     * @access public
     * @return array
     */
    public function getProductAndProject($objectType, $objectID)
    {
        $emptyRecord = array('product' => ',0,', 'project' => 0);

        /* If objectType is product or project, return the objectID. */
        if($objectType == 'product') return array('product' => ",$objectID,", 'project' => 0);
        if($objectType == 'project') 
        {
            $products = $this->dao->select('product')->from(TABLE_PROJECTPRODUCT)->where('project')->eq($objectID)->fetchPairs('product');
            $productList = ',' . join(',', array_keys($products)) . ',';
            return array('project' => $objectID, 'product' => $productList);
        }

        /* Only process these object types. */
        if(strpos(',story,productplan,release,task,build,bug,case,testtask,doc,', ",{$objectType},") !== false)
        {
            if(!isset($this->config->objectTables[$objectType])) return $emptyRecord;

            /* Set fields to fetch. */
            if(strpos('story, productplan, case',  $objectType) !== false) $fields = 'product';
            if(strpos('build, bug, testtask, doc', $objectType) !== false) $fields = 'product, project';
            if($objectType == 'release') $fields = 'product, build';
            if($objectType == 'task')    $fields = 'project, story';

            $record = $this->dao->select($fields)->from($this->config->objectTables[$objectType])->where('id')->eq($objectID)->fetch();

            /* Process story, release and task. */
            if($objectType == 'story')   $record->project = $this->dao->select('project')->from(TABLE_PROJECTSTORY)->where('story')->eq($objectID)->orderBy('project_desc')->limit(1)->fetch('project');
            if($objectType == 'release') $record->project = $this->dao->select('project')->from(TABLE_BUILD)->where('id')->eq($record->build)->fetch('project');
            if($objectType == 'task')    
            {
                if($record->story != 0)
                {
                    $product = $this->dao->select('product')->from(TABLE_STORY)->where('id')->eq($record->story)->fetchPairs('product');
                    $record->product = join(',', array_keys($product));
                }
                else
                {
                    $products = $this->dao->select('product')->from(TABLE_PROJECTPRODUCT)->where('project')->eq($record->project)->fetchPairs('product');
                    $record->product = join(',', array_keys($products));
                }
            }

            if($record)
            {
                $record = (array)$record;
                $record['product'] = isset($record['product']) ? ',' . $record['product'] . ',' : ',0,';
                if(!isset($record['project'])) $record['project'] = 0;
                return $record;
            }

            return $emptyRecord;
        }
        return $emptyRecord;
    }

    /**
     * Get actions of an object.
     * 
     * @param  int    $objectType 
     * @param  int    $objectID 
     * @access public
     * @return array
     */
    public function getList($objectType, $objectID)
    {
        $commiters = $this->loadModel('user')->getCommiters();
        $actions   = $this->dao->select('*')->from(TABLE_ACTION)
            ->beginIF($objectType == 'project')
            ->where("objectType IN('project', 'testtask', 'build')")
            ->andWhere('project')->eq($objectID)
            ->fi()
            ->beginIF($objectType != 'project')
            ->where('objectType')->eq($objectType)
            ->andWhere('objectID')->eq($objectID)
            ->fi()
            ->orderBy('date, id')->fetchAll('id');
        $histories = $this->getHistory(array_keys($actions));
        $this->loadModel('file');

        if($objectType == 'project')
        {
            $this->app->loadLang('build');
            $this->app->loadLang('testtask');
            $actions = $this->processProjectActions($actions);
        }

        foreach($actions as $actionID => $action)
        {
            $actionName = strtolower($action->action);
            if($actionName == 'svncommited' and isset($commiters[$action->actor]))
            {
                $action->actor = $commiters[$action->actor];
            }
            elseif($actionName == 'gitcommited' and isset($commiters[$action->actor]))
            {
                $action->actor = $commiters[$action->actor];
            }
            elseif($actionName == 'linked2project')
            {
                $name = $this->dao->select('name')->from(TABLE_PROJECT)->where('id')->eq($action->extra)->fetch('name');
                if($name) $action->extra = common::hasPriv('project', 'story') ? html::a(helper::createLink('project', 'story', "projectID=$action->extra"), $name) : $name;
            }
            elseif($actionName == 'linked2plan')
            {
                $title = $this->dao->select('title')->from(TABLE_PRODUCTPLAN)->where('id')->eq($action->extra)->fetch('title');
                if($title) $action->extra = common::hasPriv('productplan', 'view') ? html::a(helper::createLink('productplan', 'view', "planID=$action->extra"), $title) : $title;
            }
            elseif($actionName == 'linked2build')
            {
                $name = $this->dao->select('name')->from(TABLE_BUILD)->where('id')->eq($action->extra)->fetch('name');
                if($name) $action->extra = common::hasPriv('build', 'view') ? html::a(helper::createLink('build', 'view', "builID=$action->extra&type={$action->objectType}"), $name) : $name;
            }
            elseif($actionName == 'linked2bug')
            {
                $name = $this->dao->select('name')->from(TABLE_BUILD)->where('id')->eq($action->extra)->fetch('name');
                if($name) $action->extra = common::hasPriv('build', 'view') ? html::a(helper::createLink('build', 'view', "builID=$action->extra&type={$action->objectType}"), $name) : $name;
            }
            elseif($actionName == 'linked2release')
            {
                $name = $this->dao->select('name')->from(TABLE_RELEASE)->where('id')->eq($action->extra)->fetch('name');
                if($name) $action->extra = common::hasPriv('release', 'view') ? html::a(helper::createLink('release', 'view', "releaseID=$action->extra&type={$action->objectType}"), $name) : $name;
            }
            elseif($actionName == 'moved')
            {
                $name = $this->dao->select('name')->from(TABLE_PROJECT)->where('id')->eq($action->extra)->fetch('name');
                if($name) $action->extra = common::hasPriv('project', 'task') ? html::a(helper::createLink('project', 'task', "projectID=$action->extra"), "#$action->extra " . $name) : "#$action->extra " . $name;
            }
            elseif($actionName == 'frombug' and common::hasPriv('bug', 'view'))
            {
                $action->extra = html::a(helper::createLink('bug', 'view', "bugID=$action->extra"), $action->extra);
            }
            elseif($actionName == 'unlinkedfromproject')
            {
                $name = $this->dao->select('name')->from(TABLE_PROJECT)->where('id')->eq($action->extra)->fetch('name');
                if($name) $action->extra = common::hasPriv('project', 'story') ? html::a(helper::createLink('project', 'story', "projectID=$action->extra"), "#$action->extra " . $name) : "#$action->extra " . $name;
            }
            elseif($actionName == 'unlinkedfrombuild')
            {
                $name = $this->dao->select('name')->from(TABLE_BUILD)->where('id')->eq($action->extra)->fetch('name');
                if($name) $action->extra = common::hasPriv('build', 'view') ? html::a(helper::createLink('build', 'view', "builID=$action->extra&type={$action->objectType}"), $name) : $name;
            }
            elseif($actionName == 'unlinkedfromrelease')
            {
                $name = $this->dao->select('name')->from(TABLE_RELEASE)->where('id')->eq($action->extra)->fetch('name');
                if($name) $action->extra = common::hasPriv('release', 'view') ? html::a(helper::createLink('release', 'view', "releaseID=$action->extra&type={$action->objectType}"), $name) : $name;
            }
            elseif($actionName == 'unlinkedfromplan')
            {
                $title = $this->dao->select('title')->from(TABLE_PRODUCTPLAN)->where('id')->eq($action->extra)->fetch('title');
                if($title) $action->extra = common::hasPriv('productplan', 'view') ? html::a(helper::createLink('productplan', 'view', "planID=$action->extra"), "#$action->extra " . $title) : "#$action->extra " . $title;
            }
            elseif($actionName == 'tostory')
            {
                $title = $this->dao->select('title')->from(TABLE_STORY)->where('id')->eq($action->extra)->fetch('title');
                if($title) $action->extra = common::hasPriv('story', 'view') ? html::a(helper::createLink('story', 'view', "storyID=$action->extra"), "#$action->extra " . $title) : "#$action->extra " . $title;
            }
            elseif($actionName == 'createchildren')
            {
                $names = $this->dao->select('id,name')->from(TABLE_TASK)->where('id')->in($action->extra)->fetchPairs('id', 'name');
                $action->extra = '';
                if($names)
                {
                    foreach($names as $id => $name) $action->extra .= common::hasPriv('task', 'view') ? html::a(helper::createLink('task', 'view', "taskID=$id"), "#$id " . $name) . ', ' : "#$id " . $name . ', ';
                }
                $action->extra = trim(trim($action->extra), ',');
            }
            elseif($actionName == 'totask' or $actionName == 'linkchildtask' or $actionName == 'unlinkchildrentask' or $actionName == 'linkparenttask' or $actionName == 'unlinkparenttask' or $actionName == 'deletechildrentask')
            {
                $name = $this->dao->select('name')->from(TABLE_TASK)->where('id')->eq($action->extra)->fetch('name');
                if($name) $action->extra = common::hasPriv('task', 'view') ? html::a(helper::createLink('task', 'view', "taskID=$action->extra"), "#$action->extra " . $name) : "#$action->extra " . $name;
            }
            elseif($actionName == 'buildopened')
            {
                $name = $this->dao->select('name')->from(TABLE_BUILD)->where('id')->eq($action->objectID)->fetch('name');
                if($name) $action->extra = common::hasPriv('build', 'view') ? html::a(helper::createLink('build', 'view', "buildID=$action->objectID"), "#$action->objectID " . $name) : "#$action->objectID " . $name;
            }
            elseif($actionName == 'testtaskopened' or $actionName == 'testtaskstarted' or $actionName == 'testtaskclosed')
            {
                $name = $this->dao->select('name')->from(TABLE_TESTTASK)->where('id')->eq($action->objectID)->fetch('name');
                if($name) $action->extra = common::hasPriv('testtask', 'view') ? html::a(helper::createLink('testtask', 'view', "testtaskID=$action->objectID"), "#$action->objectID " . $name) : "#$action->objectID " . $name;
            }
            elseif($actionName == 'fromlib' and $action->objectType == 'case')
            {
                $name = $this->dao->select('name')->from(TABLE_TESTSUITE)->where('id')->eq($action->extra)->fetch('name');
                if($name) $action->extra = common::hasPriv('testsuite', 'library') ? html::a(helper::createLink('testsuite', 'library', "libID=$action->extra"), $name) : $name;
            }
            elseif(($actionName == 'closed' and $action->objectType == 'story') or ($actionName == 'resolved' and $action->objectType == 'bug'))
            {
                $action->appendLink = '';
                if(strpos($action->extra, ':')!== false)
                {
                    list($extra, $id) = explode(':', $action->extra);
                    $action->extra    = $extra;
                    if($id)
                    {
                        $table = $action->objectType == 'story' ? TABLE_STORY : TABLE_BUG;
                        $name  = $this->dao->select('title')->from($table)->where('id')->eq($id)->fetch('title');
                        if($name) $action->appendLink = html::a(helper::createLink($action->objectType, 'view', "id=$id"), "#$id " . $name);
                    }
                }
            }
            elseif($actionName == 'finished' and $objectType == 'todo')
            {
                $action->appendLink = '';
                if(strpos($action->extra, ':')!== false)
                {
                    list($extra, $id) = explode(':', $action->extra);
                    $action->extra    = strtolower($extra);
                    if($id)
                    {
                        $table = $this->config->objectTables[$action->extra];
                        $field = $this->config->action->objectNameFields[$action->extra];
                        $name  = $this->dao->select($field)->from($table)->where('id')->eq($id)->fetch($field);
                        if($name) $action->appendLink = html::a(helper::createLink($action->extra, 'view', "id=$id"), "#$id " . $name);
                    }
                }
            }
            elseif(($actionName == 'opened' or $actionName == 'managed') and $objectType == 'project')
            {
                $linkedProducts = $this->dao->select('id,name')->from(TABLE_PRODUCT)->where('id')->in($action->extra)->fetchPairs('id', 'name');
                $action->extra  = '';
                if($linkedProducts)
                {
                    foreach($linkedProducts as $productID => $productName) $linkedProducts[$productID] = html::a(helper::createLink('product', 'browse', "productID=$productID"), "#{$productID} {$productName}");
                    $action->extra = sprintf($this->lang->project->action->extra, '<strong>' . join(', ', $linkedProducts) . '</strong>');
                }
            }
            $action->history = isset($histories[$actionID]) ? $histories[$actionID] : array();

            $actionName = strtolower($action->action);
            if($actionName == 'svncommited')
            {
                foreach($action->history as $history)
                {
                    if($history->field == 'subversion') $history->diff = str_replace('+', '%2B', $history->diff);
                }
            }
            elseif($actionName == 'gitcommited')
            {
                foreach($action->history as $history)
                {
                    if($history->field == 'git') $history->diff = str_replace('+', '%2B', $history->diff);
                }
            }
            
            $action->comment = $this->file->setImgSize($action->comment, $this->config->action->commonImgSize);
            $actions[$actionID] = $action;
        }

        return $actions;
    }

    /**
     * process Project Actions change actionStype
     * 
     * @param  array    $actions 
     * @access public
     * @return void
     */
    public function processProjectActions($actions)
    {
        /* Define the action map table. */
        $map = array();
        $map['testtask']['opened']  = 'testtaskopened';
        $map['testtask']['started'] = 'testtaskstarted';
        $map['testtask']['closed']  = 'testtaskclosed';
        $map['build']['opened']     = 'buildopened';

        /* Process actions. */
        foreach($actions as $key => $action)
        {
            if($action->objectType != 'project' and !isset($map[$action->objectType][$action->action])) unset($actions[$key]);
            if(isset($map[$action->objectType][$action->action])) $action->action = $map[$action->objectType][$action->action];
        }

        return $actions;
    }

    /**
     * Get an action record.
     * 
     * @param  int    $actionID 
     * @access public
     * @return object
     */
    public function getById($actionID)
    {
        return $this->dao->findById((int)$actionID)->from(TABLE_ACTION)->fetch();
    }

    /**
     * Get deleted objects.
     * 
     * @param  string    $type all|hidden 
     * @param  string    $orderBy 
     * @param  object    $pager 
     * @access public
     * @return array
     */
    public function getTrashes($type, $orderBy, $pager)
    {
        $extra = $type == 'hidden' ? self::BE_HIDDEN : self::CAN_UNDELETED;
        $trashes = $this->dao->select('*')->from(TABLE_ACTION)
            ->where('action')->eq('deleted')
            ->andWhere('extra')->eq($extra)
            ->orderBy($orderBy)->page($pager)->fetchAll();
        if(!$trashes) return array();

        /* Group trashes by objectType, and get there name field. */
        foreach($trashes as $object) 
        {
            $object->objectType = str_replace('`', '', $object->objectType);
            $typeTrashes[$object->objectType][] = $object->objectID;
        }
        foreach($typeTrashes as $objectType => $objectIds)
        {
            if(!isset($this->config->objectTables[$objectType]))continue;

            $objectIds   = array_unique($objectIds);
            $table       = $this->config->objectTables[$objectType];
            $field       = $this->config->action->objectNameFields[$objectType];

            $objectNames[$objectType] = $this->dao->select("id, $field AS name")->from($table)->where('id')->in($objectIds)->fetchPairs();
        }

        /* Add name field to the trashes. */
        foreach($trashes as $trash) $trash->objectName = isset($objectNames[$trash->objectType][$trash->objectID]) ? $objectNames[$trash->objectType][$trash->objectID] : '';
        return $trashes;
    }

    /**
     * Get histories of an action.
     * 
     * @param  int    $actionID 
     * @access public
     * @return array
     */
    public function getHistory($actionID)
    {
        return $this->dao->select()->from(TABLE_HISTORY)->where('action')->in($actionID)->orderBy('id')->fetchGroup('action');
    }

    /**
     * Log histories for an action.
     * 
     * @param  int    $actionID 
     * @param  array  $changes 
     * @access public
     * @return void
     */
    public function logHistory($actionID, $changes)
    {
        if(empty($actionID)) return false;
        foreach($changes as $change) 
        {
            $change['action'] = $actionID;
            $this->dao->insert(TABLE_HISTORY)->data($change)->exec();
        }
    }

    /**
     * Print actions of an object.
     * 
     * @param  array    $action 
     * @access public
     * @return void
     */
    public function printAction($action, $desc = '')
    {
        if(!isset($action->objectType) or !isset($action->action)) return false;

        $objectType = $action->objectType;
        $actionType = strtolower($action->action);

        /**
         * Set the desc string of this action.
         *
         * 1. If the module of this action has defined desc of this actionType, use it.
         * 2. If no defined in the module language, search the common action define.
         * 3. If not found in the lang->action->desc, use the $lang->action->desc->common or $lang->action->desc->extra as the default.
         */
        if(empty($desc))
        {
            if(isset($this->lang->$objectType) && isset($this->lang->$objectType->action->$actionType))
            {
                $desc = $this->lang->$objectType->action->$actionType;
            }
            elseif(isset($this->lang->action->desc->$actionType))
            {
                $desc = $this->lang->action->desc->$actionType;
            }
            else
            {
                $desc = $action->extra ? $this->lang->action->desc->extra : $this->lang->action->desc->common;
            }
        }

        if($this->app->getViewType() == 'mhtml') $action->date = date('m-d H:i', strtotime($action->date));

        /* Cycle actions, replace vars. */
        foreach($action as $key => $value)
        {
            if($key == 'history') continue;

            /* Desc can be an array or string. */
            if(is_array($desc))
            {
                if($key == 'extra') continue;
                $desc['main'] = str_replace('$' . $key, $value, $desc['main']);
            }
            else
            {
                $desc = str_replace('$' . $key, $value, $desc);
            }
        }

        /* If the desc is an array, process extra. Please bug/lang. */
        if(is_array($desc))
        {
            $extra = strtolower($action->extra);

            /* Fix bug #741. */
            if(isset($desc['extra'])) $desc['extra'] = $this->lang->$objectType->{$desc['extra']};

            if(isset($desc['extra'][$extra])) 
            {
                echo str_replace('$extra', $desc['extra'][$extra], $desc['main']);
            }
            else
            {
                echo str_replace('$extra', $action->extra, $desc['main']);
            }
        }
        else
        {
            echo $desc;
        }
    }

    /**
     * Get actions as dynamic.
     * 
     * @param  string $objectType 
     * @param  string $count 
     * @param  string $period 
     * @param  string $orderBy 
     * @param  object $pager
     * @param  string|int $productID   all|int(like 123)|notzero   all => include zeror, notzero, great than 0
     * @param  string|int $projectID   same as productID
     * @param  string $date
     * @param  string $direction
     * @access public
     * @return array
     */
    public function getDynamic($account = 'all', $period = 'all', $orderBy = 'date_desc', $pager = null, $productID = 'all', $projectID = 'all', $date = '', $direction = 'next')
    {
        /* Computer the begin and end date of a period. */
        $beginAndEnd = $this->computeBeginAndEnd($period);
        extract($beginAndEnd);

        /* Build has priv condition. */
        $condition = 1;
        if($productID == 'all') $products = $this->app->user->view->products;
        if($projectID == 'all') $projects = $this->app->user->view->projects;
        if($productID == 'all' or $projectID == 'all')
        {
            $projectCondition = $projectID == 'all' ? "project " . helper::dbIN($projects) : '';
            $productCondition = $productID == 'all' ? "INSTR('," . $products . ",', product) > 0" : '';
            if(is_numeric($productID)) $productCondition = "product like'%,$productID,%' or product='$productID'";
            if(is_numeric($projectID)) $projectCondition = "project='$projectID'";

            $condition = "(product =',0,' AND project = '0')";
            if($projectCondition) $condition .= ' OR ' . $projectCondition;
            if($productCondition) $condition .= ' OR ' . $productCondition;
            if($this->app->user->admin) $condition = 1; 
        }

        $this->loadModel('doc');
        $libs = $this->doc->getLibs('all');
        $docs = $this->doc->getPrivDocs(array_keys($libs));
        
        $actionCondition = $this->getActionCondition();
        if(is_array($actionCondition)) return array();
        /* Get actions. */
        $actions = $this->dao->select('*')->from(TABLE_ACTION)
            ->where(1)
            ->beginIF($period != 'all')->andWhere('date')->gt($begin)->fi()
            ->beginIF($period != 'all')->andWhere('date')->lt($end)->fi()
            ->beginIF($date)->andWhere('date' . ($direction == 'next' ? '<' : '>') . "'{$date}'")->fi()
            ->beginIF($account != 'all')->andWhere('actor')->eq($account)->fi()
            ->beginIF(is_numeric($productID))->andWhere('product')->like("%,$productID,%")->fi()
            ->beginIF(is_numeric($projectID))->andWhere('project')->eq($projectID)->fi()
            ->beginIF($productID == 'notzero')->andWhere('product')->gt(0)->andWhere('product')->notlike('%,0,%')->fi()
            ->beginIF($projectID == 'notzero')->andWhere('project')->gt(0)->fi()
            ->beginIF($projectID == 'all' or $productID == 'all')->andWhere("IF((objectType!= 'doc' && objectType!= 'doclib'), ($condition), '1=1')")->fi()
            ->beginIF($docs and !$this->app->user->admin)->andWhere("IF(objectType != 'doc', '1=1', objectID " . helper::dbIN($docs) . ")")->fi()
            ->beginIF($libs and !$this->app->user->admin)->andWhere("IF(objectType != 'doclib', '1=1', objectID " . helper::dbIN(array_keys($libs)) . ') ')->fi()
            ->beginIF($this->config->global->flow == 'onlyStory')->andWhere('objectType')->notin('bug,build,project,task,taskcase,testreport,testsuite,testtask')->fi()
            ->beginIF($this->config->global->flow == 'onlyTask')->andWhere('objectType')->notin('product,productplan,release,story,testcase,testreport,testsuite')->fi()
            ->beginIF($this->config->global->flow == 'onlyTest')->andWhere('objectType')->notin('project,productplan,release,story,task')->fi()
            ->beginIF(!empty($actionCondition))->andWhere("($actionCondition)")->fi()
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll();

        if(!$actions) return array();

        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'action');
       
        return $this->transformActions($actions);;
    }

    /**
     * Get dynamic show action
     * 
     * @return String
     */
    public function getActionCondition()
    {
        $actionCondition = '';
        if(isset($this->app->user->rights['acls']['actions']))
        {
            if(empty($this->app->user->rights['acls']['actions'])) return array();
            foreach($this->app->user->rights['acls']['actions'] as $moduleName => $actions)
            {
                $actionCondition .= "(`objectType` = '$moduleName' and `action` " . helper::dbIN($actions) . ") or ";
            }
            $actionCondition = trim($actionCondition, 'or ');
        }
        return $actionCondition;
    }

    /**
     * Get dynamic by search. 
     * 
     * @param  array  $products 
     * @param  array  $projects 
     * @param  int    $queryID 
     * @param  string $orderBy 
     * @param  object $pager 
     * @param  string $date
     * @param  string $direction
     * @access public
     * @return array 
     */
    public function getDynamicBySearch($products, $projects, $queryID, $orderBy = 'date_desc', $pager = null, $date = '', $direction = 'next')
    {
        $query = $queryID ? $this->loadModel('search')->getQuery($queryID) : '';

        /* Get the sql and form status from the query. */
        if($query)
        {
            $this->session->set('actionQuery', $query->sql);
            $this->session->set('actionForm', $query->form);
        }
        if($this->session->actionQuery == false) $this->session->set('actionQuery', ' 1 = 1');

        $allProduct  = "`product` = 'all'";
        $allProject  = "`project` = 'all'";
        $actionQuery = $this->session->actionQuery;

        $productID = 0;
        if(preg_match("/`product` = '(\d*)'/", $actionQuery, $out))
        {
            $productID = $out[1];
        }
        /* If the sql not include 'product', add check purview for product. */
        if(strpos($actionQuery, $allProduct) === false)
        {
            if(!in_array($productID, array_keys($products))) return array();
        }
        else
        {
            $actionQuery = str_replace($allProduct, '1', $actionQuery);
        }

        /* If the sql not include 'project', add check purview for project. */
        if(strpos($actionQuery, $allProject) === false)
        {
            $actionQuery = $actionQuery . ' AND `project`' . helper::dbIN(array_keys($projects));
        }
        else
        {
            $actionQuery = str_replace($allProject, '1', $actionQuery);
        }

        $actionQuery = str_replace("`product` = '$productID'", "`product` LIKE '%,$productID,%'", $actionQuery);

        if($date) $actionQuery = "($actionQuery) AND " . ('date' . ($direction == 'next' ? '<' : '>') . "'{$date}'");
        $actions = $this->getBySQL($actionQuery, $orderBy, $pager);
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'action');
        if(!$actions) return array();
        return $this->transformActions($actions);
    }

    /**
     * Get actions by SQL. 
     * 
     * @param  string $sql 
     * @param  string $orderBy 
     * @param  object $pager 
     * @access public
     * @return array 
     */
    public function getBySQL($sql, $orderBy, $pager = null)
    {
        $actionCondition = $this->getActionCondition();
        if(is_array($actionCondition)) return array();
        return $actions = $this->dao->select('*')->from(TABLE_ACTION)
            ->where($sql)
            ->beginIF(!empty($actionCondition))->andWhere("($actionCondition)")->fi()
            ->beginIF($this->config->global->flow == 'onlyStory')->andWhere('objectType')->notin('bug,build,project,task,taskcase,testreport,testsuite,testtask')->fi()
            ->beginIF($this->config->global->flow == 'onlyTask')->andWhere('objectType')->notin('product,productplan,release,story,testcase,testreport,testsuite')->fi()
            ->beginIF($this->config->global->flow == 'onlyTest')->andWhere('objectType')->notin('project,productplan,release,story,task')->fi()
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll();
    }

    /**
     * Transform the actions for display.
     * 
     * @param  int    $actions 
     * @access public
     * @return void
     */
    public function transformActions($actions)
    {
        $this->app->loadLang('todo');
        /* Get commiters. */
        $commiters = $this->loadModel('user')->getCommiters();

        /* Group actions by objectType, and get there name field. */
        foreach($actions as $object) $objectTypes[$object->objectType][] = $object->objectID;
        foreach($objectTypes as $objectType => $objectIds)
        {
            if(!isset($this->config->objectTables[$objectType])) continue;    // If no defination for this type, omit it.

            $objectIds   = array_unique($objectIds);
            $table       = $this->config->objectTables[$objectType];
            $field       = $this->config->action->objectNameFields[$objectType];
            if($table != TABLE_TODO)
            {
                $objectNames[$objectType] = $this->dao->select("id, $field AS name")->from($table)->where('id')->in($objectIds)->fetchPairs();
            }
            else
            {
                $todos = $this->dao->select("id, $field AS name, account, private, type, idvalue")->from($table)->where('id')->in($objectIds)->fetchAll('id');
                foreach($todos as $id => $todo)
                {
                    if($todo->type == 'task') $todo->name = $this->dao->findById($todo->idvalue)->from(TABLE_TASK)->fetch('name');
                    if($todo->type == 'bug')  $todo->name = $this->dao->findById($todo->idvalue)->from(TABLE_BUG)->fetch('title');
                    if($todo->private == 1 and $todo->account != $this->app->user->account) 
                    {
                        $objectNames[$objectType][$id] = $this->lang->todo->thisIsPrivate;
                    }
                    else
                    {
                        $objectNames[$objectType][$id] = $todo->name;
                    }
                }
            } 
        }
        $objectNames['user'][0] = 'guest';    // Add guest account.

        foreach($actions as $i => $action)
        {
            /* Add name field to the actions. */
            $action->objectName = isset($objectNames[$action->objectType][$action->objectID]) ? $objectNames[$action->objectType][$action->objectID] : '';

            $actionType = strtolower($action->action);
            $objectType = strtolower($action->objectType);
            $action->originalDate = $action->date;
            $action->date         = date(DT_MONTHTIME2, strtotime($action->date));
            $action->actionLabel  = isset($this->lang->action->label->$actionType) ? $this->lang->action->label->$actionType : $action->action;
            $action->objectLabel  = $objectType;
            if(isset($this->lang->action->label->$objectType))
            {
                $objectLabel = $this->lang->action->label->$objectType;
                if(!is_array($objectLabel)) $action->objectLabel = $objectLabel;
                if(is_array($objectLabel) and isset($objectLabel[$actionType])) $action->objectLabel = $objectLabel[$actionType];
            }

            /* If action type is login or logout, needn't link. */
            if($actionType == 'svncommited')
            {
                $action->actor = isset($commiters[$action->actor]) ? $commiters[$action->actor] : $action->actor;
            }

            /* Other actions, create a link. */
            if(strpos($action->objectLabel, '|') !== false)
            {
                list($objectLabel, $moduleName, $methodName, $vars) = explode('|', $action->objectLabel);
                $action->objectLink = '';
                if(!common::hasPriv($moduleName, $methodName))
                {
                    unset($actions[$i]);
                    continue;
                }

                $action->objectLink  = helper::createLink($moduleName, $methodName, sprintf($vars, $action->objectID));
                if($action->objectType == 'user') $action->objectLink  = helper::createLink($moduleName, $methodName, sprintf($vars, $action->objectName));
                $action->objectLabel = $objectLabel;
            }
            else
            {
                $action->objectLink = '';
                $action->objectLabel = zget($this->lang->action->objectTypes, $action->objectLabel);
            }

            $action->major = (isset($this->config->action->majorList[$action->objectType]) && in_array($action->action, $this->config->action->majorList[$action->objectType])) ? 1 : 0;
        }
        return $actions;
    }

    /**
     * Compute the begin date and end date of a period.
     * 
     * @param  string    $period   all|today|yesterday|twodaysago|latest2days|thisweek|lastweek|thismonth|lastmonth
     * @access public
     * @return array
     */
    public function computeBeginAndEnd($period)
    {
        $this->app->loadClass('date');

        $today      = date('Y-m-d');
        $tomorrow   = date::tomorrow();
        $yesterday  = date::yesterday();
        $twoDaysAgo = date::twoDaysAgo();

        $period = strtolower($period);

        if($period == 'all')        return array('begin' => '1970-1-1',  'end' => '2109-1-1');
        if($period == 'today')      return array('begin' => $today,      'end' => $tomorrow);
        if($period == 'yesterday')  return array('begin' => $yesterday,  'end' => $today);
        if($period == 'twodaysago') return array('begin' => $twoDaysAgo, 'end' => $yesterday);
        if($period == 'latest3days')return array('begin' => $twoDaysAgo, 'end' => $tomorrow);

        /* If the period is by week, add the end time to the end date. */
        if($period == 'thisweek' or $period == 'lastweek')
        {
            $func = "get$period";
            extract(date::$func());
            return array('begin' => $begin, 'end' => $end . ' 23:59:59');
        }

        if($period == 'thismonth')  return date::getThisMonth();
        if($period == 'lastmonth')  return date::getLastMonth();
    }

    /**
     * Print changes of every action.
     * 
     * @param  string    $objectType 
     * @param  array     $histories 
     * @access public
     * @return void
     */
    public function printChanges($objectType, $histories, $canChangeTag = true)
    {
        if(empty($histories)) return;

        $maxLength            = 0;          // The max length of fields names.
        $historiesWithDiff    = array();    // To save histories without diff info.
        $historiesWithoutDiff = array();    // To save histories with diff info.

        /* Diff histories by hasing diff info or not. Thus we can to make sure the field with diff show at last. */
        foreach($histories as $history)
        {
            $fieldName = $history->field;
            $history->fieldLabel = (isset($this->lang->$objectType) && isset($this->lang->$objectType->$fieldName)) ? $this->lang->$objectType->$fieldName : $fieldName;
            if(($length = strlen($history->fieldLabel)) > $maxLength) $maxLength = $length;
            $history->diff ? $historiesWithDiff[] = $history : $historiesWithoutDiff[] = $history;
        }
        $histories = array_merge($historiesWithoutDiff, $historiesWithDiff);

        foreach($histories as $history)
        {
            $history->fieldLabel = str_pad($history->fieldLabel, $maxLength, $this->lang->action->label->space);
            if($history->diff != '')
            {
                $history->diff      = str_replace(array('<ins>', '</ins>', '<del>', '</del>'), array('[ins]', '[/ins]', '[del]', '[/del]'), $history->diff);
                $history->diff      = ($history->field != 'subversion' and $history->field != 'git') ? htmlspecialchars($history->diff) : $history->diff;   // Keep the diff link.
                $history->diff      = str_replace(array('[ins]', '[/ins]', '[del]', '[/del]'), array('<ins>', '</ins>', '<del>', '</del>'), $history->diff);
                $history->diff      = nl2br($history->diff);
                $history->noTagDiff = $canChangeTag ? preg_replace('/&lt;\/?([a-z][a-z0-9]*)[^\/]*\/?&gt;/Ui', '', $history->diff) : '';
                printf($this->lang->action->desc->diff2, $history->fieldLabel, $history->noTagDiff, $history->diff);
            }
            else
            {
                printf($this->lang->action->desc->diff1, $history->fieldLabel, $history->old, $history->new);
            }
        }
    }

    /**
     * Undelete a record.
     * 
     * @param  int      $actionID 
     * @access public
     * @return void
     */
    public function undelete($actionID)
    {
        $action = $this->getById($actionID);
        if($action->action != 'deleted') return;
        if($action->objectType == 'product')
        {
            $product = $this->dao->select('name,code')->from(TABLE_PRODUCT)->where('id')->eq($action->objectID)->fetch();
            $count   = $this->dao->select('COUNT(*) AS count')->from(TABLE_PRODUCT)->where('deleted')->eq('0')->andWhere("(`name`='{$product->name}' OR `code`='{$product->code}')")->fetch('count');
            if($count > 0)
            {
                echo js::alert(sprintf($this->lang->action->needEdit, $this->lang->action->objectTypes['product']));
                die(js::locate(helper::createLink('product', 'edit', "productID=$action->objectID&action=undelete&extra=$actionID"), 'parent'));
            }
        }
        elseif($action->objectType == 'project')
        {
            $project = $this->dao->select('name,code')->from(TABLE_PROJECT)->where('id')->eq($action->objectID)->fetch();
            $count   = $this->dao->select('COUNT(*) AS count')->from(TABLE_PROJECT)->where('deleted')->eq('0')->andWhere("(`name`='{$project->name}' OR `code`='{$project->code}')")->fetch('count');
            if($count > 0)
            {
                echo js::alert(sprintf($this->lang->action->needEdit, $this->lang->action->objectTypes['project']));
                die(js::locate(helper::createLink('project', 'edit', "projectID=$action->objectID&action=undelete&extra=$actionID"), 'parent'));
            }
        }
        elseif($action->objectType == 'module')
        {
            $module     = $this->dao->select('*')->from(TABLE_MODULE)->where('id')->eq($action->objectID)->fetch();
            $repeatName = $this->loadModel('tree')->checkUnique($module);
            if($repeatName) die(js::alert(sprintf($this->lang->tree->repeatName, $repeatName)));
        }

        /* Update deleted field in object table. */
        $table = $this->config->objectTables[$action->objectType];
        $this->dao->update($table)->set('deleted')->eq(0)->where('id')->eq($action->objectID)->exec();

        /* Revert doclib when undelete product or project. */
        if($action->objectType == 'project' or $action->objectType == 'product')
        {
            $this->dao->update(TABLE_DOCLIB)->set('deleted')->eq(0)->where($action->objectType)->eq($action->objectID)->exec();
        }

        /* Revert productplan parent status. */
        if($action->objectType == 'productplan') $this->loadModel('productplan')->changeParentField($action->objectID);

        /* Update task status when undelete child task. */
        if($action->objectType == 'task') $this->loadModel('task')->updateParentStatus($action->objectID);

        /* Update action record in action table. */
        $this->dao->update(TABLE_ACTION)->set('extra')->eq(ACTIONMODEL::BE_UNDELETED)->where('id')->eq($actionID)->exec();
        $this->create($action->objectType, $action->objectID, 'undeleted');
    }

    /**
     * Hide an object. 
     * 
     * @param  int    $actionID 
     * @access public
     * @return void
     */
    public function hideOne($actionID)
    {
        $action = $this->getById($actionID);
        if($action->action != 'deleted') return;

        $this->dao->update(TABLE_ACTION)->set('extra')->eq(self::BE_HIDDEN)->where('id')->eq($actionID)->exec();
        $this->create($action->objectType, $action->objectID, 'hidden');
    }

    /**
     * Hide all deleted objects.
     * 
     * @access public
     * @return void
     */
    public function hideAll()
    {
        $this->dao->update(TABLE_ACTION)
            ->set('extra')->eq(self::BE_HIDDEN)
            ->where('action')->eq('deleted')
            ->andWhere('extra')->eq(self::CAN_UNDELETED)
            ->exec();
    }

    /**
     * Update comment of a action.
     * 
     * @param  int    $actionID 
     * @access public
     * @return void
     */
    public function updateComment($actionID)
    {
        $action = $this->getById($actionID);
        $action->comment = trim(strip_tags($this->post->lastComment, $this->config->allowedTags));

        /* Process action. */
        $action = $this->loadModel('file')->processImgURL($action, 'comment', $this->post->uid);

        $this->dao->update(TABLE_ACTION)
            ->set('date')->eq(helper::now())
            ->set('comment')->eq($action->comment)
            ->where('id')->eq($actionID)
            ->exec();
        $this->file->updateObjectID($this->post->uid, $action->objectID, $action->objectType);
    }

    /**
     * Build date group by actions
     * 
     * @param  array  $actions 
     * @param  string $direction 
     * @access public
     * @return array
     */
    public function buildDateGroup($actions, $direction = 'next')
    {
        $dateGroup = array();
        foreach($actions as $action)
        {
            $timeStamp    = strtotime(isset($action->originalDate) ? $action->originalDate : $action->date);
            $date         = date(DT_DATE4, $timeStamp);
            $action->time = date(DT_TIME2, $timeStamp);
            $dateGroup[$date][] = $action;
        }

        if($dateGroup)
        {
            $lastDateActions = $this->dao->select('*')->from(TABLE_ACTION)->where($this->session->actionQueryCondition)->andWhere("(LEFT(`date`, 10) = '" . substr($action->originalDate, 0, 10) . "')")->orderBy($this->session->actionOrderBy)->fetchAll('id');
            if(count($dateGroup[$date]) < count($lastDateActions))
            {
                unset($dateGroup[$date]);
                $lastDateActions = $this->transformActions($lastDateActions);
                foreach($lastDateActions as $action)
                {
                    $timeStamp    = strtotime(isset($action->originalDate) ? $action->originalDate : $action->date);
                    $date         = date(DT_DATE4, $timeStamp);
                    $action->time = date(DT_TIME2, $timeStamp);
                    $dateGroup[$date][] = $action;
                }
            }
        }

        if($direction != 'next') $dateGroup = array_reverse($dateGroup);
        return $dateGroup;
    }

    /**
     * Check Has pre or next.
     * 
     * @param  string $date 
     * @param  string $direction 
     * @access public
     * @return bool
     */
    public function hasPreOrNext($date, $direction = 'next')
    {
        $condition = $this->session->actionQueryCondition;
        /* Remove date condition for direction. */
        $condition = preg_replace("/AND +date[\<\>]'\d{4}\-\d{2}\-\d{2}'/", '', $condition);
        $count     = $this->dao->select('count(*) as count')->from(TABLE_ACTION)->where($condition)
            ->andWhere('date' . ($direction == 'next' ? '<' : '>') . "'{$date}'")
            ->fetch('count');
        return $count > 0;
    }
}
