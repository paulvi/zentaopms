<?php
/**
 * The model file of case module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     case
 * @version     $Id: model.php 5108 2013-07-12 01:59:04Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php
class testcaseModel extends model
{
    /**
     * Set menu.
     *
     * @param  array  $products
     * @param  int    $productID
     * @param  int    $branch
     * @param  int    $moduleID
     * @param  int    $suiteID
     * @param  string $orderBy
     * @access public
     * @return void
     */
    public function setMenu($products, $productID, $branch = 0, $moduleID = 0, $suiteID = 0, $orderBy = 'id_desc')
    {
        $this->loadModel('product')->setMenu($products, $productID, $branch, $moduleID, 'case');
        $selectHtml = $this->product->select($products, $productID, 'testcase', 'browse', '', $branch, $moduleID, 'case');

        $pageNav     = '';
        $pageActions = '';
        $isMobile    = $this->app->viewType == 'mhtml';
        if($isMobile)
        {
            $this->app->loadLang('qa');
            $pageNav  = html::a(helper::createLink('qa', 'index'), $this->lang->qa->index) . $this->lang->colon;
        }
        else
        {
            if($this->config->global->flow == 'full')
            {
                $this->app->loadLang('qa');
                $pageNav = '<div class="btn-group angle-btn"><div class="btn-group">' . html::a(helper::createLink('qa', 'index', 'locate=no'), $this->lang->qa->index, '', "class='btn'") . '</div></div>';
            }
            else
            {
                $exportPriv        = common::hasPriv('testcase', 'export');
                $exportTempletPriv = common::hasPriv('testcase', 'exportTemplet');
                $importPriv        = common::hasPriv('testcase', 'import');
                $importFromLibPriv = common::hasPriv('testcase', 'importFromLib');
                if($exportPriv or $exportTempletPriv)
                {
                    $pageActions .= "<div class='btn-group'>";
                    $pageActions .= "<button type='button' class='btn btn-link dropdown-toggle' data-toggle='dropdown'>";
                    $pageActions .= "<i class='icon icon-export muted'></i> {$this->lang->export}";
                    $pageActions .= "<span class='caret'></span>";
                    $pageActions .= '</button>';
                    $pageActions .= "<ul class='dropdown-menu' id='exportActionMenu'>";
                    if($exportPriv)
                    {
                        $link = helper::createLink('testcase', 'export', "productID=$productID&orderBy=$orderBy");
                        $pageActions .= '<li>' . html::a($link, $this->lang->testcase->export, '', "class='export'") . '</li>';
                    }
                    if($exportTempletPriv)
                    {
                        $link = helper::createLink('testcase', 'exportTemplet', "productID=$productID");
                        $pageActions .= '<li>' . html::a($link, $this->lang->testcase->exportTemplet, '', "class='export'") . '</li>';
                    }
                    $pageActions .= '</ul>';
                    $pageActions .= '</div>';

                }
                if($importPriv or $importFromLibPriv)
                {
                    $pageActions .= "<div class='btn-group'>";
                    $pageActions .= "<button type='button' class='btn btn-link dropdown-toggle' data-toggle='dropdown' id='importAction'><i class='icon icon-import muted'></i> {$this->lang->import}<span class='caret'></span></button>";
                    $pageActions .= "<ul class='dropdown-menu' id='importActionMenu'>";
                    if($importPriv)
                    {
                        $link = helper::createLink('testcase', 'import', "productID=$productID&branch=$branch");
                        $pageActions .= '<li>' . html::a($link, $this->lang->testcase->importFile, '', "class='export'") . '</li>';
                    }
                    if($importFromLibPriv)
                    {
                        $link = helper::createLink('testcase', 'importFromLib', "productID=$productID&branch=$branch");
                        $pageActions .= '<li>' . html::a($link, $this->lang->testcase->importFromLib) . '</li>';
                    }
                    $pageActions .= '</ul>';
                    $pageActions .= '</div>';
                }
                $initModule = isset($moduleID) ? (int)$moduleID : 0;

                if(common::hasPriv('testcase', 'batchCreate'))
                {
                    $link = helper::createLink('testcase', 'batchCreate', "productID=$productID&branch=$branch&moduleID=$initModule");
                    $pageActions .= html::a($link, "<i class='icon-plus'></i> " . $this->lang->testcase->batchCreate, '', "class='btn btn-secondary'");
                }

                if(common::hasPriv('testcase', 'create'))
                {
                    $link = helper::createLink('testcase', 'create', "productID=$productID&branch=$branch&moduleID=$initModule");
                    $pageActions .= html::a($link, "<i class='icon-plus'></i> " . $this->lang->testcase->create, '', "class='btn btn-primary'");
                }
            }
        }
        $pageNav .= $selectHtml;

        $this->lang->modulePageNav     = $pageNav;
        $this->lang->modulePageActions = $pageActions;
        foreach($this->lang->testcase->menu as $key => $menu)
        {
            if($this->config->global->flow != 'onlyTest')
            {
                $replace = $productID;
            }
            else
            {
                if($key == 'bysuite')
                {
                    $subMenu = array();
                    if(common::hasPriv('testcase', 'browse'))
                    {
                        $suiteList      = $this->loadModel('testsuite')->getSuites($productID);
                        $currentSuiteID = isset($suiteID) ? (int)$suiteID : 0;

                        if($suiteList)
                        {
                            foreach($suiteList as $suiteID => $suite)
                            {
                                $suiteName = $suite->name;
                                if($suite->type == 'public') $suiteName .= " <span class='label label-info'>{$this->lang->testsuite->authorList[$suite->type]}</span>";

                                $link = array();
                                $link['module'] = 'testcase';
                                $link['method'] = 'browse';
                                $link['vars']   = "productID=$productID&branch=$branch&browseType=bysuite&param=$suiteID";

                                $menu = new stdclass();
                                $menu->name   = $suiteID;
                                $menu->link   = $link;
                                $menu->text   = $suiteName;
                                $menu->hidden = false;
                                $subMenu[$suiteID] = $menu;
                            }
                        }
                    }
                    /* Avoid the menu shaking when change it by js. */
                    if(isset($subMenu[$currentSuiteID]))
                    {
                        $currentSubMenu = $subMenu[$currentSuiteID];
                        $this->lang->testcase->menu->bysuite['link'] = "$currentSubMenu->text|" . implode('|', $currentSubMenu->link);
                    }

                    /* Replace for dropdown submenu. */
                    if(isset($this->lang->testcase->subMenu->$key))
                    {
                        $subMenu += common::createSubMenu($this->lang->testcase->subMenu->$key, $productID);
                    }
                    if(!empty($subMenu)) $this->lang->testcase->menu->{$key}['subMenu'] = $subMenu;

                    if($this->app->getMethodName() != 'view') $this->lang->testcase->menu->bysearch = "<a class='querybox-toggle' id='bysearchTab'><i class='icon icon-search muted'> </i>{$this->lang->testcase->bySearch}</a>";
                }
                else
                {
                    $replace = array();
                    $replace['productID'] = $productID;
                    $replace['branch']    = $branch;
                }
            }
            common::setMenuVars($this->lang->testcase->menu, $key, $replace);
        }
    }

    /**
     * Create a case.
     *
     * @param int $bugID
     * @access public
     * @return void
     */
    function create($bugID)
    {
        $now    = helper::now();
        $status = $this->getStatus('create');
        $case   = fixer::input('post')
            ->add('status', $status)
            ->add('version', 1)
            ->add('fromBug', $bugID)
            ->setDefault('openedBy', $this->app->user->account)
            ->setDefault('openedDate', $now)
            ->setIF($this->post->story != false, 'storyVersion', $this->loadModel('story')->getVersion((int)$this->post->story))
            ->remove('steps,expects,files,labels,stepType,forceNotReview')
            ->setDefault('story', 0)
            ->join('stage', ',')
            ->get();

        $param = '';
        if(!empty($case->lib))$param = "lib={$case->lib}";
        if(!empty($case->product))$param = "product={$case->product}";
        $result = $this->loadModel('common')->removeDuplicate('case', $case, $param);
        if($result['stop']) return array('status' => 'exists', 'id' => $result['duplicate']);

        /* value of story may be showmore. */
        $case->story = (int)$case->story;
        $this->dao->insert(TABLE_CASE)->data($case)->autoCheck()->batchCheck($this->config->testcase->create->requiredFields, 'notempty')->exec();
        if(!$this->dao->isError())
        {
            $caseID = $this->dao->lastInsertID();
            $this->loadModel('file')->saveUpload('testcase', $caseID);
            $parentStepID = 0;
            $this->loadModel('score')->create('testcase', 'create', $caseID);
            foreach($this->post->steps as $stepID => $stepDesc)
            {
                if(empty($stepDesc)) continue;
                $stepType      = $this->post->stepType;
                $step          = new stdClass();
                $step->type    = ($stepType[$stepID] == 'item' and $parentStepID == 0) ? 'step' : $stepType[$stepID];
                $step->parent  = ($step->type == 'item') ? $parentStepID : 0;
                $step->case    = $caseID;
                $step->version = 1;
                $step->desc    = htmlspecialchars($stepDesc);
                $step->expect  = $step->type == 'group' ? '' : htmlspecialchars($this->post->expects[$stepID]);
                $this->dao->insert(TABLE_CASESTEP)->data($step)->autoCheck()->exec();
                if($step->type == 'group') $parentStepID = $this->dao->lastInsertID();
                if($step->type == 'step')  $parentStepID = 0;
            }
            return array('status' => 'created', 'id' => $caseID);
        }
    }

    /**
     * Batch create cases.
     *
     * @param  int    $productID
     * @param  int    $storyID
     * @access public
     * @return void
     */
    function batchCreate($productID, $branch, $storyID)
    {
        $branch      = (int)$branch;
        $now         = helper::now();
        $cases       = fixer::input('post')->get();

        $result = $this->loadModel('common')->removeDuplicate('case', $cases, "product={$productID}");
        $cases  = $result['data'];

        foreach($cases->title as $i => $title)
        {
            if(!empty($cases->title[$i]) and empty($cases->type[$i])) die(js::alert(sprintf($this->lang->error->notempty, $this->lang->testcase->type)));
        }

        $module = 0;
        $story  = 0;
        $type   = '';
        $pri    = 3;
        foreach($cases->title as $i => $title)
        {
            $module = $cases->module[$i] == 'ditto' ? $module : $cases->module[$i];
            $story  = $cases->story[$i] == 'ditto'  ? $story  : $cases->story[$i];
            $type   = $cases->type[$i] == 'ditto'   ? $type   : $cases->type[$i];
            $pri    = $cases->pri[$i] == 'ditto'    ? $pri    : $cases->pri[$i];
            $cases->module[$i] = (int)$module;
            $cases->story[$i]  = (int)$story;
            $cases->type[$i]   = $type;
            $cases->pri[$i]    = $pri;
        }

        $this->loadModel('story');
        $storyVersions  = array();
        $forceNotReview = $this->forceNotReview();
        $data           = array();
        foreach($cases->title as $i => $title)
        {
            if(empty($title)) continue;

            $data[$i] = new stdclass();
            $data[$i]->product      = $productID;
            $data[$i]->branch       = $cases->branch[$i];
            $data[$i]->module       = $cases->module[$i];
            $data[$i]->type         = $cases->type[$i];
            $data[$i]->pri          = $cases->pri[$i];
            $data[$i]->stage        = empty($cases->stage[$i]) ? '' : implode(',', $cases->stage[$i]);
            $data[$i]->story        = $storyID ? $storyID : $cases->story[$i];
            $data[$i]->color        = $cases->color[$i];
            $data[$i]->title        = $cases->title[$i];
            $data[$i]->precondition = $cases->precondition[$i];
            $data[$i]->keywords     = $cases->keywords[$i];
            $data[$i]->openedBy     = $this->app->user->account;
            $data[$i]->openedDate   = $now;
            $data[$i]->status       = $forceNotReview || $cases->needReview[$i] == 0 ? 'normal' : 'wait';
            $data[$i]->version      = 1;

            $caseStory = $data[$i]->story;
            $data[$i]->storyVersion = isset($storyVersions[$caseStory]) ? $storyVersions[$caseStory] : 0;
            if($caseStory and !isset($storyVersions[$caseStory]))
            {
                $data[$i]->storyVersion = $this->story->getVersion($caseStory);
                $storyVersions[$caseStory] = $data[$i]->storyVersion;
            }

            foreach(explode(',', $this->config->testcase->create->requiredFields) as $field)
            {
                $field = trim($field);
                if($field and empty($data[$i]->$field)) die(js::alert(sprintf($this->lang->error->notempty, $this->lang->testcase->$field)));
            }
        }

        foreach($data as $i => $case)
        {
            $this->dao->insert(TABLE_CASE)->data($case)
                ->autoCheck()
                ->batchCheck($this->config->testcase->create->requiredFields, 'notempty')
                ->exec();

            if(dao::isError())
            {
                echo js::error(dao::getError());
                die(js::reload('parent'));
            }

            $caseID   = $this->dao->lastInsertID();
            $this->loadModel('score')->create('testcase', 'create', $caseID);
            $actionID = $this->loadModel('action')->create('case', $caseID, 'Opened');
        }
        if(!dao::isError()) $this->loadModel('score')->create('ajax', 'batchCreate');
    }

    /**
     * Get cases of a module.
     *
     * @param  int    $productID
     * @param  int    $moduleIdList
     * @param  string $orderBy
     * @param  object $pager
     * @access public
     * @return array
     */
    public function getModuleCases($productID, $branch = 0, $moduleIdList = 0, $orderBy = 'id_desc', $pager = null, $browseType = '')
    {
        return $this->dao->select('t1.*, t2.title as storyTitle')->from(TABLE_CASE)->alias('t1')
            ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story=t2.id')
            ->where('t1.product')->eq((int)$productID)
            ->beginIF($branch)->andWhere('t1.branch')->eq($branch)->fi()
            ->beginIF($moduleIdList)->andWhere('t1.module')->in($moduleIdList)->fi()
            ->beginIF($browseType == 'wait')->andWhere('t1.status')->eq($browseType)->fi()
            ->andWhere('t1.deleted')->eq('0')
            ->orderBy($orderBy)->page($pager)->fetchAll('id');
    }

    /**
     * Get by suite.
     *
     * @param  int    $productID
     * @param  int    $branch
     * @param  int    $suiteID
     * @param  array  $moduleIdList
     * @param  string $orderBy
     * @param  object $pager
     * @access public
     * @return void
     */
    public function getBySuite($productID, $branch = 0, $suiteID, $moduleIdList = 0, $orderBy = 'id_desc', $pager = null)
    {
        return $this->dao->select('t1.*, t2.title as storyTitle, t3.version as version')->from(TABLE_CASE)->alias('t1')
            ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story=t2.id')
            ->leftJoin(TABLE_SUITECASE)->alias('t3')->on('t1.id=t3.case')
            ->where('t1.product')->eq((int)$productID)
            ->andWhere('t3.suite')->eq((int)$suiteID)
            ->beginIF($branch)->andWhere('t1.branch')->eq($branch)->fi()
            ->beginIF($moduleIdList)->andWhere('t1.module')->in($moduleIdList)->fi()
            ->andWhere('t1.deleted')->eq('0')
            ->orderBy($orderBy)->page($pager)->fetchAll('id');
    }

    /**
     * Get case info by ID.
     *
     * @param  int    $caseID
     * @param  int    $version
     * @access public
     * @return object|bool
     */
    public function getById($caseID, $version = 0)
    {
        $case = $this->dao->findById($caseID)->from(TABLE_CASE)->fetch();
        if(!$case) return false;
        foreach($case as $key => $value) if(strpos($key, 'Date') !== false and !(int)substr($value, 0, 4)) $case->$key = '';
        if($case->story)
        {
            $story = $this->dao->findById($case->story)->from(TABLE_STORY)->fields('title, status, version')->fetch();
            $case->storyTitle         = $story->title;
            $case->storyStatus        = $story->status;
            $case->latestStoryVersion = $story->version;
        }
        if($case->fromBug) $case->fromBugTitle      = $this->dao->findById($case->fromBug)->from(TABLE_BUG)->fields('title')->fetch('title');

        $case->toBugs = array();
        $toBugs       = $this->dao->select('id, title')->from(TABLE_BUG)->where('`case`')->eq($caseID)->fetchAll();
        foreach($toBugs as $toBug) $case->toBugs[$toBug->id] = $toBug->title;

        if($case->linkCase or $case->fromCaseID) $case->linkCaseTitles = $this->dao->select('id,title')->from(TABLE_CASE)->where('id')->in($case->linkCase)->orWhere('id')->eq($case->fromCaseID)->fetchPairs();
        if($version == 0) $version = $case->version;
        $case->steps = $this->dao->select('*')->from(TABLE_CASESTEP)->where('`case`')->eq($caseID)->andWhere('version')->eq($version)->orderBy('id')->fetchAll('id');
        $case->files = $this->loadModel('file')->getByObject('testcase', $caseID);
        $case->currentVersion = $version ? $version : $case->version;
        return $case;
    }

    /**
     * Get case list.
     *
     * @param  int|array|string $caseIDList
     * @access public
     * @return array
     */
    public function getByList($caseIDList = 0)
    {
        return $this->dao->select('*')->from(TABLE_CASE)
            ->where('deleted')->eq(0)
            ->beginIF($caseIDList)->andWhere('id')->in($caseIDList)->fi()
            ->fetchAll('id');
    }

    /**
     * Get test cases.
     *
     * @param  int    $productID
     * @param  int    $branch
     * @param  string $browseType
     * @param  int    $queryID
     * @param  int    $moduleID
     * @param  string $sort
     * @param  object $pager
     * @access public
     * @return array
     */
    public function getTestCases($productID, $branch, $browseType, $queryID, $moduleID, $sort, $pager)
    {
        /* Set modules and browse type. */
        $modules    = $moduleID ? $this->loadModel('tree')->getAllChildId($moduleID) : '0';
        $browseType = ($browseType == 'bymodule' and $this->session->caseBrowseType and $this->session->caseBrowseType != 'bysearch') ? $this->session->caseBrowseType : $browseType;

        /* By module or all cases. */
        $cases = array();
        if($browseType == 'bymodule' or $browseType == 'all' or $browseType == 'wait')
        {
            $cases = $this->getModuleCases($productID, $branch, $modules, $sort, $pager, $browseType);
        }
        /* Cases need confirmed. */
        elseif($browseType == 'needconfirm')
        {
            $cases = $this->dao->select('t1.*, t2.title AS storyTitle')->from(TABLE_CASE)->alias('t1')->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
                ->where("t2.status = 'active'")
                ->andWhere('t1.deleted')->eq(0)
                ->andWhere('t2.version > t1.storyVersion')
                ->andWhere('t1.product')->eq($productID)
                ->beginIF($branch)->andWhere('t1.branch')->eq($branch)->fi()
                ->beginIF($modules)->andWhere('t1.module')->in($modules)->fi()
                ->orderBy($sort)
                ->page($pager)
                ->fetchAll();
        }
        elseif($browseType == 'bysuite')
        {
            $cases = $this->getBySuite($productID, $branch, $queryID, $modules, $sort, $pager);
        }
        /* By search. */
        elseif($browseType == 'bysearch')
        {
            $cases = $this->getBySearch($productID, $queryID, $sort, $pager, $branch);
        }

        return $cases;
    }

    /**
     * Get cases by search.
     *
     * @param  int    $productID
     * @param  int    $queryID
     * @param  string $orderBy
     * @param  object $pager
     * @access public
     * @return array
     */
    public function getBySearch($productID, $queryID, $orderBy, $pager = null, $branch = 0)
    {
        if($queryID)
        {
            $query = $this->loadModel('search')->getQuery($queryID);
            if($query)
            {
                $this->session->set('testcaseQuery', $query->sql);
                $this->session->set('testcaseForm', $query->form);
            }
            else
            {
                $this->session->set('testcaseQuery', ' 1 = 1');
            }
        }
        else
        {
            if($this->session->testcaseQuery == false) $this->session->set('testcaseQuery', ' 1 = 1');
        }

        $queryProductID = $productID;
        $allProduct     = "`product` = 'all'";
        $caseQuery      = '(' . $this->session->testcaseQuery;
        if(strpos($this->session->testcaseQuery, $allProduct) !== false)
        {
            $products  = $this->app->user->view->products;
            $caseQuery = str_replace($allProduct, '1', $caseQuery);
            $caseQuery = $caseQuery . ' AND `product` ' . helper::dbIN($products);
            $queryProductID = 'all';
        }

        $allBranch = "`branch` = 'all'";
        if($branch and strpos($caseQuery, '`branch` =') === false) $caseQuery .= " AND `branch` in('0','$branch')";
        if(strpos($caseQuery, $allBranch) !== false) $caseQuery = str_replace($allBranch, '1', $caseQuery);
        $caseQuery .= ')';

        $cases = $this->dao->select('*')->from(TABLE_CASE)->where($caseQuery)
            ->beginIF($queryProductID != 'all')->andWhere('product')->eq($productID)->fi()
            ->andWhere('deleted')->eq(0)
            ->orderBy($orderBy)->page($pager)->fetchAll('id');

        return $cases;
    }

    /**
     * Get cases by assignedTo.
     *
     * @param  string $account
     * @param  string $orderBy
     * @param  object $pager
     * @access public
     * @return array
     */
    public function getByAssignedTo($account, $orderBy = 'id_desc', $pager = null)
    {
        return $this->dao->select('t1.*,t2.pri,t2.title,t2.type,t2.openedBy,t2.color,t2.product,t2.branch,t2.module,t2.status')->from(TABLE_TESTRUN)->alias('t1')
            ->leftJoin(TABLE_CASE)->alias('t2')->on('t1.case = t2.id')
            ->leftJoin(TABLE_TESTTASK)->alias('t3')->on('t1.task = t3.id')
            ->where('t1.assignedTo')->eq($account)
            ->andWhere('t1.status')->ne('done')
            ->andWhere('t3.status')->ne('done')
            ->andWhere('t3.deleted')->eq(0)
            ->andWhere('t2.deleted')->eq(0)
            ->orderBy($orderBy)->page($pager)->fetchAll();
    }

    /**
     * Get cases by openedBy
     *
     * @param  string $account
     * @param  string $orderBy
     * @param  object $pager
     * @access public
     * @return array
     */
    public function getByOpenedBy($account, $orderBy = 'id_desc', $pager = null)
    {
        return $this->dao->findByOpenedBy($account)->from(TABLE_CASE)
            ->andWhere('product')->ne(0)
            ->andWhere('deleted')->eq(0)
            ->orderBy($orderBy)->page($pager)->fetchAll();
    }

    /**
     * Get cases of a story.
     *
     * @param  int    $storyID
     * @access public
     * @return array
     */
    public function getStoryCases($storyID)
    {
        return $this->dao->select('id, title, pri, type, status, lastRunner, lastRunDate, lastRunResult')
            ->from(TABLE_CASE)
            ->where('story')->eq((int)$storyID)
            ->andWhere('deleted')->eq(0)
            ->fetchAll('id');
    }

    /**
     * Get counts of some stories' cases.
     *
     * @param  array  $stories
     * @access public
     * @return int
     */
    public function getStoryCaseCounts($stories)
    {
        $caseCounts = $this->dao->select('story, COUNT(*) AS cases')
            ->from(TABLE_CASE)
            ->where('story')->in($stories)
            ->andWhere('deleted')->eq(0)
            ->groupBy('story')
            ->fetchPairs();
        foreach($stories as $storyID) if(!isset($caseCounts[$storyID])) $caseCounts[$storyID] = 0;
        return $caseCounts;
    }

    /**
     * Update a case.
     *
     * @param  int    $caseID
     * @access public
     * @return void
     */
    public function update($caseID)
    {
        $now     = helper::now();
        $oldCase = $this->getById($caseID);

        $result = $this->getStatus('update', $oldCase);
        if(!$result or !is_array($result)) return $result;

        list($stepChanged, $status) = $result;

        $version = $stepChanged ? $oldCase->version + 1 : $oldCase->version;

        $case = fixer::input('post')
            ->add('version', $version)
            ->setIF($this->post->story != false and $this->post->story != $oldCase->story, 'storyVersion', $this->loadModel('story')->getVersion($this->post->story))
            ->setDefault('lastEditedBy',   $this->app->user->account)
            ->add('lastEditedDate', $now)
            ->setDefault('story,branch', 0)
            ->join('stage', ',')
            ->join('linkCase', ',')
            ->setForce('status', $status)
            ->remove('comment,steps,expects,files,labels,stepType')
            ->get();

        $this->dao->update(TABLE_CASE)->data($case)->autoCheck()->batchCheck($this->config->testcase->edit->requiredFields, 'notempty')->where('id')->eq((int)$caseID)->exec();
        if(!$this->dao->isError())
        {
            if($stepChanged)
            {
                $parentStepID = 0;
                $isLibCase = ($oldCase->lib and empty($oldCase->product));
                if($isLibCase) 
                {
                    $fromcaseVersion  = $this->dao->select('fromCaseVersion')->from(TABLE_CASE)->where('fromCaseID')->eq($caseID)->fetch('fromCaseVersion');
                    $fromcaseVersion += 1;
                    $this->dao->update(TABLE_CASE)->set('`fromCaseVersion`')->eq($fromcaseVersion)->where('`fromCaseID`')->eq($caseID)->exec(); 
                }

                /* Ignore steps when post has no steps. */
                if($this->post->steps)
                {
                    foreach($this->post->steps as $stepID => $stepDesc)
                    {
                        if(empty($stepDesc)) continue;
                        $stepType = $this->post->stepType;
                        $step = new stdclass();
                        $step->type    = ($stepType[$stepID] == 'item' and $parentStepID == 0) ? 'step' : $stepType[$stepID];
                        $step->parent  = ($step->type == 'item') ? $parentStepID : 0;
                        $step->case    = $caseID;
                        $step->version = $version;
                        $step->desc    = htmlspecialchars($stepDesc);
                        $step->expect  = $step->type == 'group' ? '' : htmlspecialchars($this->post->expects[$stepID]);
                        $this->dao->insert(TABLE_CASESTEP)->data($step)->autoCheck()->exec();
                        if($step->type == 'group') $parentStepID = $this->dao->lastInsertID();
                        if($step->type == 'step')  $parentStepID = 0;
                    }
                }
                else
                {
                    foreach($oldCase->steps as $step)
                    {
                        unset($step->id);
                        $step->version = $version;
                        $this->dao->insert(TABLE_CASESTEP)->data($step)->autoCheck()->exec();
                    }
                }
            }

            /* Join the steps to diff. */
            if($stepChanged and $this->post->steps)
            {
                $oldCase->steps = $this->joinStep($oldCase->steps);
                $case->steps    = $this->joinStep($this->getById($caseID, $version)->steps);
            }
            else
            {
                unset($oldCase->steps);
            }
            return common::createChanges($oldCase, $case);
        }
    }

    /**
     * Review case
     *
     * @param  int    $caseID
     * @access public
     * @return bool | array
     */
    public function review($caseID)
    {
        if($this->post->result == false) die(js::alert($this->lang->testcase->mustChooseResult));

        $oldCase = $this->getById($caseID);

        $now    = helper::now();
        $status = $this->getStatus('review', $oldCase);
        $case   = fixer::input('post')
            ->remove('result,comment')
            ->setDefault('reviewedDate', substr($now, 0, 10))
            ->setDefault('lastEditedBy', $this->app->user->account)
            ->setDefault('lastEditedDate', $now)
            ->setForce('status', $status)
            ->join('reviewedBy', ',')
            ->get();

        $this->dao->update(TABLE_CASE)->data($case)->autoCheck()->where('id')->eq($caseID)->exec();

        if(dao::isError()) return false;

        return common::createChanges($oldCase, $case);
    }

    /**
     * Batch review cases.
     *
     * @param  array   $caseIDList
     * @access public
     * @return array
     */
    public function batchReview($caseIdList, $result)
    {
        $now     = helper::now();
        $actions = array();
        $this->loadModel('action');

        $oldCases = $this->getByList($caseIdList);
        foreach($caseIdList as $caseID)
        {
            $oldCase = $oldCases[$caseID];
            if($oldCase->status != 'wait') continue;

            $case = new stdClass();
            $case->reviewedBy     = $this->app->user->account;
            $case->reviewedDate   = substr($now, 0, 10);
            $case->lastEditedBy   = $this->app->user->account;
            $case->lastEditedDate = $now;
            if($result == 'pass') $case->status = 'normal';
            $this->dao->update(TABLE_CASE)->data($case)->autoCheck()->where('id')->eq($caseID)->exec();
            $actions[$caseID] = $this->action->create('case', $caseID, 'Reviewed', '', ucfirst($result));
        }

        return $actions;
    }

    /**
     * Get cases to link.
     *
     * @param  int    $caseID
     * @param  string $browseType
     * @param  int    $queryID
     * @access public
     * @return array
     */
    public function getCases2Link($caseID, $browseType = 'bySearch', $queryID)
    {
        if($browseType == 'bySearch')
        {
            $case       = $this->getById($caseID);
            $cases2Link = $this->getBySearch($case->product, $queryID, 'id', null, $case->branch);
            foreach($cases2Link as $key => $case2Link)
            {
                if($case2Link->id == $caseID) unset($cases2Link[$key]);
                if(in_array($case2Link->id, explode(',', $case->linkCase))) unset($cases2Link[$key]);
            }
            return $cases2Link;
        }
        else
        {
            return array();
        }
    }

    /**
     * Batch update testcases.
     *
     * @access public
     * @return array
     */
    public function batchUpdate()
    {
        $cases      = array();
        $allChanges = array();
        $now        = helper::now();
        $data       = fixer::input('post')->get();
        $caseIDList = $this->post->caseIDList;

        /* Process data if the value is 'ditto'. */
        foreach($caseIDList as $caseID)
        {
            if($data->pris[$caseID]     == 'ditto') $data->pris[$caseID]     = isset($prev['pri'])    ? $prev['pri']    : 3;
            if($data->branches[$caseID] == 'ditto') $data->branches[$caseID] = isset($prev['branch']) ? $prev['branch'] : 0;
            if($data->modules[$caseID]  == 'ditto') $data->modules[$caseID]  = isset($prev['module']) ? $prev['module'] : 0;
            if($data->stories[$caseID]  == 'ditto') $data->stories[$caseID]  = isset($prev['story'])  ? $prev['story']  : 0;
            if($data->types[$caseID]    == 'ditto') $data->types[$caseID]    = isset($prev['type'])   ? $prev['type']   : '';
            if($data->stories[$caseID]  == '')      $data->stories[$caseID]  = 0;

            $prev['pri']    = $data->pris[$caseID];
            $prev['type']   = $data->types[$caseID];
            $prev['story']  = $data->stories[$caseID];
            $prev['branch'] = $data->branches[$caseID];
            $prev['module'] = $data->modules[$caseID];
        }

        /* Initialize cases from the post data.*/
        foreach($caseIDList as $caseID)
        {
            $case = new stdclass();
            $case->lastEditedBy   = $this->app->user->account;
            $case->lastEditedDate = $now;
            $case->pri            = $data->pris[$caseID];
            $case->status         = $data->statuses[$caseID];
            $case->branch         = $data->branches[$caseID];
            $case->module         = $data->modules[$caseID];
            $case->story          = $data->stories[$caseID];
            $case->color          = $data->color[$caseID];
            $case->title          = $data->title[$caseID];
            $case->precondition   = $data->precondition[$caseID];
            $case->keywords       = $data->keywords[$caseID];
            $case->type           = $data->types[$caseID];
            $case->stage          = empty($data->stages[$caseID]) ? '' : implode(',', $data->stages[$caseID]);

            $cases[$caseID] = $case;
            unset($case);
        }

        /* Update cases. */
        foreach($cases as $caseID => $case)
        {
            $oldCase = $this->getByID($caseID);
            $this->dao->update(TABLE_CASE)->data($case)
                ->autoCheck()
                ->batchCheck($this->config->testcase->edit->requiredFields, 'notempty')
                ->where('id')->eq($caseID)
                ->exec();

            if(!dao::isError())
            {
                unset($oldCase->steps);
                $allChanges[$caseID] = common::createChanges($oldCase, $case);
            }
            else
            {
                die(js::error('case#' . $caseID . dao::getError(true)));
            }
        }

        return $allChanges;
    }

    /**
     * Batch change branch.
     *
     * @param  array  $caseIDList
     * @param  int    $branchID
     * @access public
     * @return array
     */
    public function batchChangeBranch($caseIDList, $branchID)
    {
        $now        = helper::now();
        $allChanges = array();
        $oldCases   = $this->getByList($caseIDList);
        foreach($caseIDList as $caseID)
        {
            $oldCase = $oldCases[$caseID];
            if($branchID == $oldCase->branch) continue;

            $case = new stdclass();
            $case->lastEditedBy   = $this->app->user->account;
            $case->lastEditedDate = $now;
            $case->branch         = $branchID;

            $this->dao->update(TABLE_CASE)->data($case)->autoCheck()->where('id')->eq((int)$caseID)->exec();
            if(!dao::isError()) $allChanges[$caseID] = common::createChanges($oldCase, $case);
        }

        return $allChanges;
    }

    /**
     * Batch change the module of case.
     *
     * @param  array  $caseIDList
     * @param  int    $moduleID
     * @access public
     * @return array
     */
    public function batchChangeModule($caseIDList, $moduleID)
    {
        $now        = helper::now();
        $allChanges = array();
        $oldCases   = $this->getByList($caseIDList);
        foreach($caseIDList as $caseID)
        {
            $oldCase = $oldCases[$caseID];
            if($moduleID == $oldCase->module) continue;

            $case = new stdclass();
            $case->lastEditedBy   = $this->app->user->account;
            $case->lastEditedDate = $now;
            $case->module         = $moduleID;

            $this->dao->update(TABLE_CASE)->data($case)->autoCheck()->where('id')->eq((int)$caseID)->exec();
            if(!dao::isError()) $allChanges[$caseID] = common::createChanges($oldCase, $case);
        }

        return $allChanges;
    }

    /**
     * Batch case type change.
     *
     * @param  array   $caseIDList
     * @param  string  $result
     * @access public
     * @return array
     */
    public function batchCaseTypeChange($caseIdList, $result)
    {
        $now     = helper::now();
        $actions = array();
        $this->loadModel('action');

        foreach($caseIdList as $caseID)
        {
            $case = new stdClass();
            $case->lastEditedBy   = $this->app->user->account;
            $case->lastEditedDate = $now;
            $case->type           = $result;

            $this->dao->update(TABLE_CASE)->data($case)->autoCheck()->where('id')->eq($caseID)->exec();
            $this->action->create('case', $caseID, 'Edited', '', ucfirst($result));
        }
    }

    /**
     * Join steps to a string, thus can diff them.
     *
     * @param  array   $steps
     * @access public
     * @return string
     */
    public function joinStep($steps)
    {
        $return = '';
        if(empty($steps)) return $return;
        foreach($steps as $step) $return .= $step->desc . ' EXPECT:' . $step->expect . "\n";
        return $return;
    }

    /**
     * Create case steps from a bug's step.
     *
     * @param  string    $steps
     * @access public
     * @return array
     */
    function createStepsFromBug($steps)
    {
        $steps        = strip_tags($steps);
        $caseSteps    = array((object)array('desc' => $steps, 'expect' => ''));   // the default steps before parse.
        $lblStep      = strip_tags($this->lang->bug->tplStep);
        $lblResult    = strip_tags($this->lang->bug->tplResult);
        $lblExpect    = strip_tags($this->lang->bug->tplExpect);
        $lblStepPos   = strpos($steps, $lblStep);
        $lblResultPos = strpos($steps, $lblResult);
        $lblExpectPos = strpos($steps, $lblExpect);

        if($lblStepPos === false or $lblResultPos === false or $lblExpectPos === false) return $caseSteps;

        $caseSteps  = substr($steps, $lblStepPos + strlen($lblStep), $lblResultPos - strlen($lblStep) - $lblStepPos);
        $caseExpect = substr($steps, $lblExpectPos + strlen($lblExpect));
        $caseSteps  = trim($caseSteps);
        $caseExpect = trim($caseExpect);

        $caseSteps = explode("\n", trim($caseSteps));
        $stepCount = count($caseSteps);
        foreach($caseSteps as $key => $caseStep)
        {
            $expect = $key + 1 == $stepCount ? $caseExpect : '';
            $caseSteps[$key] = (object)array('desc' => trim($caseStep), 'expect' => $expect, 'type' => 'item');
        }
        return $caseSteps;
    }

    /**
     * Adjust the action is clickable.
     *
     * @param  object $case
     * @param  string $action
     * @access public
     * @return void
     */
    public static function isClickable($case, $action)
    {
        $action = strtolower($action);

        if($action == 'createbug') return $case->caseFails > 0;
        if($action == 'review') return $case->status == 'wait';

        return true;
    }

    /**
     * Create from import
     *
     * @param  int    $productID
     * @access public
     * @return void
     */
    public function createFromImport($productID, $branch = 0)
    {
        $this->loadModel('action');
        $this->loadModel('story');
        $this->loadModel('file');
        $now    = helper::now();
        $branch = (int)$branch;
        $data   = fixer::input('post')->get();

        if(!empty($_POST['id']))
        {
            $oldSteps = $this->dao->select('t2.*')->from(TABLE_CASE)->alias('t1')
                ->leftJoin(TABLE_CASESTEP)->alias('t2')->on('t1.id = t2.case')
                ->where('t1.id')->in(($_POST['id']))
                ->andWhere('t1.product')->eq($productID)
                ->andWhere('t1.version=t2.version')
                ->orderBy('t2.id')
                ->fetchGroup('case');
            $oldCases = $this->dao->select('*')->from(TABLE_CASE)->where('id')->in($_POST['id'])->fetchAll('id');
        }
        $storyVersionPairs = $this->story->getVersions($data->story);

        $cases = array();
        foreach($data->product as $key => $product)
        {
            $caseData = new stdclass();

            $caseData->product      = $product;
            $caseData->branch       = isset($data->branch[$key]) ? $data->branch[$key] : $branch;
            $caseData->module       = $data->module[$key];
            $caseData->story        = (int)$data->story[$key];
            $caseData->title        = $data->title[$key];
            $caseData->pri          = (int)$data->pri[$key];
            $caseData->type         = $data->type[$key];
            $caseData->stage        = join(',', $data->stage[$key]);
            $caseData->keywords     = $data->keywords[$key];
            $caseData->frequency    = 1;
            $caseData->precondition = $data->precondition[$key];

            if(isset($this->config->testcase->create->requiredFields))
            {
                $requiredFields = explode(',', $this->config->testcase->create->requiredFields);
                foreach($requiredFields as $requiredField)
                {
                    $requiredField = trim($requiredField);
                    if(empty($caseData->$requiredField)) dao::$errors[] = sprintf($this->lang->testcase->noRequire, $key, $this->lang->testcase->$requiredField);
                }
            }

            $cases[$key] = $caseData;
        }
        if(dao::isError()) die(js::error(dao::getError()));

        $forceNotReview = $this->forceNotReview();
        foreach($cases as $key => $caseData)
        {
            $caseID = 0;
            if(!empty($_POST['id'][$key]) and empty($_POST['insert']))
            {
                $caseID = $data->id[$key];
                if(!isset($oldCases[$caseID])) $caseID = 0;
            }

            if($caseID)
            {
                $stepChanged = false;
                $steps       = array();
                $oldStep     = isset($oldSteps[$caseID]) ? $oldSteps[$caseID] : array();
                $oldCase     = $oldCases[$caseID];

                /* Remove the empty setps in post. */
                $steps = array();
                if(isset($_POST['desc'][$key]))
                {
                    foreach($this->post->desc[$key] as $id => $desc)
                    {
                        $desc = trim($desc);
                        if(empty($desc)) continue;
                        $step = new stdclass();
                        $step->type   = $data->stepType[$key][$id];
                        $step->desc    = htmlspecialchars($desc);
                        $step->expect  = htmlspecialchars(trim($this->post->expect[$key][$id]));

                        $steps[] = $step;
                    }
                }

                /* If step count changed, case changed. */
                if((!$oldStep != !$steps) or (count($oldStep) != count($steps)))
                {
                    $stepChanged = true;
                }
                else
                {
                    /* Compare every step. */
                    foreach($oldStep as $id => $oldStep)
                    {
                        if(trim($oldStep->desc) != trim($steps[$id]->desc) or trim($oldStep->expect) != $steps[$id]->expect)
                        {
                            $stepChanged = true;
                            break;
                        }
                    }
                }

                $version           = $stepChanged ? $oldCase->version + 1 : $oldCase->version;
                $caseData->version = $version;
                $changes           = common::createChanges($oldCase, $caseData);
                if($caseData->story != $oldCase->story) $caseData->storyVersion = zget($storyVersionPairs, $caseData->story, 1);
                if(!$changes and !$stepChanged) continue;

                if($changes or $stepChanged)
                {
                    $caseData->lastEditedBy   = $this->app->user->account;
                    $caseData->lastEditedDate = $now;
                    if($stepChanged and !$forceNotReview) $caseData->status = 'wait';
                    $this->dao->update(TABLE_CASE)->data($caseData)->where('id')->eq($caseID)->autoCheck()->exec();
                    if($stepChanged)
                    {
                        $parentStepID = 0;
                        foreach($steps as $id => $step)
                        {
                            $step = (array)$step;
                            if(empty($step['desc'])) continue;
                            $stepData = new stdclass();
                            $stepData->type    = ($step['type'] == 'item' and $parentStepID == 0) ? 'step' : $step['type'];
                            $stepData->parent  = ($stepData->type == 'item') ? $parentStepID : 0;
                            $stepData->case    = $caseID;
                            $stepData->version = $version;
                            $stepData->desc    = htmlspecialchars($step['desc']);
                            $stepData->expect  = htmlspecialchars($step['expect']);
                            $this->dao->insert(TABLE_CASESTEP)->data($stepData)->autoCheck()->exec();
                            if($stepData->type == 'group') $parentStepID = $this->dao->lastInsertID();
                            if($stepData->type == 'step')  $parentStepID = 0;
                        }
                    }
                    $oldCase->steps  = $this->joinStep($oldStep);
                    $caseData->steps = $this->joinStep($steps);
                    $changes  = common::createChanges($oldCase, $caseData);
                    $actionID = $this->action->create('case', $caseID, 'Edited');
                    $this->action->logHistory($actionID, $changes);
                }
            }
            else
            {
                $caseData->version    = 1;
                $caseData->openedBy   = $this->app->user->account;
                $caseData->openedDate = $now;
                $caseData->branch     = isset($data->branch[$key]) ? $data->branch[$key] : $branch;
                if($caseData->story) $caseData->storyVersion = zget($storyVersionPairs, $caseData->story, 1);
                $caseData->status = !$forceNotReview ? 'wait' : 'normal';
                $this->dao->insert(TABLE_CASE)->data($caseData)->autoCheck()->exec();

                if(!dao::isError())
                {
                    $caseID       = $this->dao->lastInsertID();
                    $parentStepID = 0;
                    foreach($this->post->desc[$key] as $id => $desc)
                    {
                        $desc = trim($desc);
                        if(empty($desc)) continue;
                        $stepData = new stdclass();
                        $stepData->type    = ($data->stepType[$key][$id] == 'item' and $parentStepID == 0) ? 'step' : $data->stepType[$key][$id];
                        $stepData->parent  = ($stepData->type == 'item') ? $parentStepID : 0;
                        $stepData->case    = $caseID;
                        $stepData->version = 1;
                        $stepData->desc    = htmlspecialchars($desc);
                        $stepData->expect  = htmlspecialchars($this->post->expect[$key][$id]);
                        $this->dao->insert(TABLE_CASESTEP)->data($stepData)->autoCheck()->exec();
                        if($stepData->type == 'group') $parentStepID = $this->dao->lastInsertID();
                        if($stepData->type == 'step')  $parentStepID = 0;
                    }
                    $this->action->create('case', $caseID, 'Opened');
                }
            }
        }

        unlink($this->session->importFile);
        unset($_SESSION['importFile']);
    }

    /**
     * Get fields for import.
     *
     * @access public
     * @return array
     */
    public function getImportFields($productID = 0)
    {
        $product    = $this->loadModel('product')->getById($productID);
        if($product->type != 'normal') $this->lang->testcase->branch = $this->lang->product->branchName[$product->type];

        $caseLang   = $this->lang->testcase;
        $caseConfig = $this->config->testcase;
        $fields     = explode(',', $caseConfig->exportFields);
        foreach($fields as $key => $fieldName)
        {
            $fieldName = trim($fieldName);
            $fields[$fieldName] = isset($caseLang->$fieldName) ? $caseLang->$fieldName : $fieldName;
            unset($fields[$key]);
        }

        return $fields;
    }

    /**
     * Import case from Lib.
     *
     * @param  int    $productID
     * @access public
     * @return void
     */
    public function importFromLib($productID)
    {
        $data = fixer::input('post')->get();

        $prevModule = 0;
        foreach($data->module as $i => $module)
        {
            if($module != 'ditto') $prevModule = $module;
            if($module == 'ditto') $data->module[$i] = $prevModule;
        }

        $libCases = $this->dao->select('*')->from(TABLE_CASE)->where('deleted')->eq(0)->andWhere('id')->in($data->caseIdList)->fetchAll('id');
        $libSteps = $this->dao->select('*')->from(TABLE_CASESTEP)->where('`case`')->in($data->caseIdList)->orderBy('id')->fetchGroup('case');
        foreach($libCases as $libCaseID => $case)
        {
            $case->fromCaseID      = $case->id;
            $case->fromCaseVersion = $case->version;
            $case->product    = $productID;
            if(isset($data->module[$case->id])) $case->module = $data->module[$case->id];
            if(isset($data->branch[$case->id])) $case->branch = $data->branch[$case->id];
            unset($case->id);

            $this->dao->insert(TABLE_CASE)->data($case)->autoCheck()->exec();

            if(!dao::isError())
            {
                $caseID = $this->dao->lastInsertID();
                if(isset($libSteps[$libCaseID]))
                {
                    foreach($libSteps[$libCaseID] as $step)
                    {
                        $step->case = $caseID;
                        unset($step->id);
                        $this->dao->insert(TABLE_CASESTEP)->data($step)->exec();
                    }
                }
                /* Fix bug #1518. */
                $oldFile = $this->dao->select('*')->from(TABLE_FILE)->where('objectID')->eq($case->fromCaseID)->fetchAll();
                foreach($oldFile as $fileID => $File)
                {
                    $File->objectID  = $caseID;
                    $File->addedBy   = $this->app->user->account;
                    $File->addedDate = helper::today();
                    $File->downloads = 0;
                    unset($File->id);
                    $this->dao->insert(TABLE_FILE)->data($File)->exec();
                }
                $this->loadModel('action')->create('case', $caseID, 'fromlib', '', $case->lib);
            }
        }
    }

    /**
     * Build search form.
     *
     * @param  int    $productID
     * @param  array  $products
     * @param  int    $queryID
     * @param  string $actionURL
     * @access public
     * @return void
     */
    public function buildSearchForm($productID, $products, $queryID, $actionURL)
    {
        $this->config->testcase->search['params']['product']['values'] = array($productID => $products[$productID], 'all' => $this->lang->testcase->allProduct);
        $this->config->testcase->search['params']['module']['values']  = $this->loadModel('tree')->getOptionMenu($productID, $viewType = 'case');
        if($this->session->currentProductType == 'normal')
        {
            unset($this->config->testcase->search['fields']['branch']);
            unset($this->config->testcase->search['params']['branch']);
        }
        else
        {
            $this->config->testcase->search['fields']['branch'] = $this->lang->product->branch;
            $this->config->testcase->search['params']['branch']['values'] = array('' => '') + $this->loadModel('branch')->getPairs($productID, 'noempty') + array('all' => $this->lang->branch->all);
        }
        if(!$this->config->testcase->needReview) unset($this->config->testcase->search['params']['status']['values']['wait']);
        $this->config->testcase->search['actionURL'] = $actionURL;
        $this->config->testcase->search['queryID']   = $queryID;

        $this->loadModel('search')->setSearchParams($this->config->testcase->search);
    }

    /**
     * Print cell data
     *
     * @param  object $col
     * @param  object $case
     * @param  array  $users
     * @param  array  $branches
     * @access public
     * @return void
     */
    public function printCell($col, $case, $users, $branches, $modulePairs = array(), $browseType = '', $mode = 'datatable')
    {
        $canView    = common::hasPriv('testcase', 'view');
        $caseLink   = helper::createLink('testcase', 'view', "caseID=$case->id&version=$case->version");
        $account    = $this->app->user->account;
        $fromCaseID = $case->fromCaseID;
        $id = $col->id;
        if($col->show)
        {
            $class = 'c-' . $id;
            $title = '';
            if($id == 'title')
            {
                $class .= ' text-left';
                $title  = "title='{$case->title}'";
            }
            if($id == 'status')
            {
                $class .= $case->status;
                $title  = "title='" . $this->processStatus('testcase', $case) . "'";
            }
            if($id == 'actions') $class .= ' c-actions';
            if($id == 'lastRunResult') $class .= " {$case->lastRunResult}";
            if(strpos(',stage,precondition,keywords,story,', ",{$id},") !== false) $class .= ' text-ellipsis';

            echo "<td class='{$class}' {$title}>";
            if(isset($this->config->bizVersion)) $this->loadModel('flow')->printFlowCell('testcase', $case, $id);
            switch($id)
            {
            case 'id':
                echo html::checkbox('caseIDList', array($case->id => '')) . html::a(helper::createLink('testcase', 'view', "caseID=$case->id"), sprintf('%03d', $case->id));
                break;
            case 'pri':
                echo "<span class='label-pri label-pri-" . $case->pri . "' title='" . zget($this->lang->testcase->priList, $case->pri, $case->pri) . "'>";
                echo zget($this->lang->testcase->priList, $case->pri, $case->pri);
                echo "</span>";
                break;
            case 'title':
                if($case->branch) echo "<span class='label label-info label-outline'>{$branches[$case->branch]}</span> ";
                if($modulePairs and $case->module) echo "<span class='label label-gray label-badge'>{$modulePairs[$case->module]}</span> ";
                echo $canView ? ($fromCaseID ? html::a($caseLink, $case->title, null, "style='color: $case->color'") . html::a(helper::createLink('testcase', 'view', "caseID=$fromCaseID"), "[<i class='icon icon-share' title='{$this->lang->testcase->fromCase}'></i>#$fromCaseID]") : html::a($caseLink, $case->title, null, "style='color: $case->color'")) : "<span style='color: $case->color'>$case->title</span>";
                break;
            case 'branch':
                echo $branches[$case->branch];
                break;
            case 'type':
                echo $this->lang->testcase->typeList[$case->type];
                break;
            case 'stage':
                $stages = '';
                foreach(explode(',', trim($case->stage, ',')) as $stage) $stages .= $this->lang->testcase->stageList[$stage] . ',';
                $stages = trim($stages, ',');
                echo "<span title='$stages'>$stages</span>";
                break;
            case 'status':
                if($case->needconfirm) 
                {
                    print("<span class='status-story status-changed' title='{$this->lang->story->changed}'>{$this->lang->story->changed}</span>");
                }
                elseif(isset($case->fromCaseVersion) and $case->fromCaseVersion > $case->version and !$case->needconfirm)
                {
                    print("<span class='status-story status-changed' title='{$this->lang->testcase->changed}'>{$this->lang->testcase->changed}</span>");
                }
                else
                {
                    print("<span class='status-testcase status-{$case->status}'>" . $this->processStatus('testcase', $case) . "</span>");
                }
                break;
            case 'story':
                static $stories = array();
                if(empty($stories)) $stories = $this->dao->select('id,title')->from(TABLE_STORY)->where('deleted')->eq('0')->andWhere('product')->eq($case->product)->fetchPairs('id', 'title');
                if($case->story and isset($stories[$case->story])) echo html::a(helper::createLink('story', 'view', "storyID=$case->story"), $stories[$case->story]);
                break;
            case 'precondition':
                echo $case->precondition;
                break;
            case 'keywords':
                echo $case->keywords;
                break;
            case 'version':
                echo $case->version;
                break;
            case 'openedBy':
                echo zget($users, $case->openedBy);
                break;
            case 'openedDate':
                echo substr($case->openedDate, 5, 11);
                break;
            case 'reviewedBy':
                echo zget($users, $case->reviewedBy);
                break;
            case 'reviewedDate':
                echo substr($case->reviewedDate, 5, 11);
                break;
            case 'lastEditedBy':
                echo zget($users, $case->lastEditedBy);
                break;
            case 'lastEditedDate':
                echo substr($case->lastEditedDate, 5, 11);
                break;
            case 'lastRunner':
                echo zget($users, $case->lastRunner);
                break;
            case 'lastRunDate':
                if(!helper::isZeroDate($case->lastRunDate)) echo date(DT_MONTHTIME1, strtotime($case->lastRunDate));
                break;
            case 'lastRunResult':
                $class = 'result-' . $case->lastRunResult;
                $lastRunResultText = $case->lastRunResult ? zget($this->lang->testcase->resultList, $case->lastRunResult, $case->lastRunResult) : $this->lang->testcase->unexecuted;
                echo "<span class='$class'>" . $lastRunResultText . "</span>";
                break;
            case 'bugs':
                echo (common::hasPriv('testcase', 'bugs') and $case->bugs) ? html::a(helper::createLink('testcase', 'bugs', "runID=0&caseID={$case->id}"), $case->bugs, '', "class='iframe'") : $case->bugs;
                break;
            case 'results':
                echo (common::hasPriv('testtask', 'results') and $case->results) ? html::a(helper::createLink('testtask', 'results', "runID=0&caseID={$case->id}"), $case->results, '', "class='iframe'") : $case->results;
                break;
            case 'stepNumber':
                echo $case->stepNumber;
                break;
            case 'actions':
                if($case->needconfirm or $browseType == 'needconfirm')
                {
                    common::printIcon('testcase', 'confirmstorychange',  "caseID=$case->id", $case, 'list', 'confirm', 'hiddenwin', '', '', '', $this->lang->confirm);
                    break;
                }

                common::printIcon('testtask', 'results', "runID=0&caseID=$case->id", $case, 'list', '', '', 'iframe', true, "data-width='95%'");
                common::printIcon('testtask', 'runCase', "runID=0&caseID=$case->id&version=$case->version", $case, 'list', 'play', '', 'runCase iframe', false, "data-width='95%'");
                common::printIcon('testcase', 'edit',    "caseID=$case->id", $case, 'list');
                if($this->config->testcase->needReview or !empty($this->config->testcase->forceReview)) common::printIcon('testcase', 'review',  "caseID=$case->id", $case, 'list', 'glasses', '', 'iframe');
                common::printIcon('testcase', 'createBug', "product=$case->product&branch=$case->branch&extra=caseID=$case->id,version=$case->version,runID=", $case, 'list', 'bug', '', 'iframe', '', "data-width='90%'");
                common::printIcon('testcase', 'create',  "productID=$case->product&branch=$case->branch&moduleID=$case->module&from=testcase&param=$case->id", $case, 'list', 'copy');

                break;
            }
            echo '</td>';
        }
    }

    /**
     * Append bugs and results.
     *
     * @param  array    $cases
     * @param  string   $type
     * @access public
     * @return array
     */
    public function appendData($cases, $type = 'case')
    {
        $caseIdList = array_keys($cases);
        if($type == 'case')
        {
            $caseBugs   = $this->dao->select('count(*) as count, `case`')->from(TABLE_BUG)->where('`case`')->in($caseIdList)->andWhere('deleted')->eq(0)->groupBy('`case`')->fetchPairs('case', 'count');
            $results    = $this->dao->select('count(*) as count, `case`')->from(TABLE_TESTRESULT)->where('`case`')->in($caseIdList)->groupBy('`case`')->fetchPairs('case', 'count');

            $caseFails = $this->dao->select('count(*) as count, `case`')->from(TABLE_TESTRESULT)
                ->where('caseResult')->eq('fail')
                ->andwhere('`case`')->in($caseIdList)
                ->groupBy('`case`')
                ->fetchPairs('case','count');

            $steps = $this->dao->select('count(distinct t1.id) as count, t1.`case`')->from(TABLE_CASESTEP)->alias('t1')
                ->leftJoin(TABLE_CASE)->alias('t2')->on('t1.`case`=t2.`id`')
                ->where('t1.`case`')->in($caseIdList)
                ->andWhere('t1.type')->ne('group')
                ->andWhere('t1.version=t2.version')
                ->groupBy('t1.`case`')
                ->fetchPairs('case', 'count');
        }
        else
        {
            $caseBugs = $this->dao->select('count(*) as count, `case`')->from(TABLE_BUG)->where('`result`')->in($caseIdList)->andWhere('deleted')->eq(0)->groupBy('`case`')->fetchPairs('case', 'count');
            $results  = $this->dao->select('count(*) as count, `case`')->from(TABLE_TESTRESULT)->where('`run`')->in($caseIdList)->groupBy('`run`')->fetchPairs('case', 'count');

            $caseFails = $this->dao->select('count(*) as count, `case`')->from(TABLE_TESTRESULT)
                ->where('caseResult')->eq('fail')
                ->andwhere('`run`')->in($caseIdList)
                ->groupBy('`case`')
                ->fetchPairs('case','count');

            $steps = $this->dao->select('count(distinct t1.id) as count, t1.`case`')->from(TABLE_CASESTEP)->alias('t1')
                ->leftJoin(TABLE_TESTRUN)->alias('t2')->on('t1.`case`=t2.`case`')
                ->where('t2.`id`')->in($caseIdList)
                ->andWhere('t1.type')->ne('group')
                ->andWhere('t1.version=t2.version')
                ->groupBy('t1.`case`')
                ->fetchPairs('case', 'count');
        }

        foreach($cases as $key => $case)
        {
            $caseID = $type == 'case' ? $case->id : $case->case;
            $case->bugs       = isset($caseBugs[$caseID])  ? $caseBugs[$caseID]   : 0;
            $case->results    = isset($results[$caseID])   ? $results[$caseID]    : 0;
            $case->caseFails  = isset($caseFails[$caseID]) ? $caseFails[$caseID]  : 0;
            $case->stepNumber = isset($steps[$caseID])     ? $steps[$caseID]      : 0;
        }

        return $cases;
    }

    /**
     * Check whether force not review.
     *
     * @access public
     * @return bool
     */
    public function forceNotReview()
    {
        if(empty($this->config->testcase->needReview))
        {
            if(!isset($this->config->testcase->forceReview)) return true;
            if(strpos(",{$this->config->testcase->forceReview},", ",{$this->app->user->account},") === false) return true;
        }
        if($this->config->testcase->needReview && strpos(",{$this->config->testcase->forceNotReview},", ",{$this->app->user->account},") !== false) return true;

        return false;
    }

    public function summary($cases)
    {
        $executed = 0;
        foreach($cases as $case)
        {
            if($case->lastRunResult != '') $executed ++;
        }

        return sprintf($this->lang->testcase->summary, count($cases), $executed);
    }

    /**
     * Get status for different method.
     *
     * @param  string $methodName
     * @param  object $case
     * @access public
     * @return mixed    string | bool | array
     */
    public function getStatus($methodName, $case = null)
    {
        if($methodName == 'create')
        {
            if($this->forceNotReview() || $this->post->forceNotReview) return 'normal';
            return 'wait';
        }

        if($methodName == 'review')
        {
            $status = zget($case, 'status', $status);

            if($this->post->result == 'pass') return 'normal';

            return $status;
        }

        if($methodName == 'update')
        {
            if(!empty($_POST['lastEditedDate']) and $case->lastEditedDate != $this->post->lastEditedDate)
            {
                dao::$errors[] = $this->lang->error->editedByOther;
                return false;
            }

            $status      = $this->post->status ? $this->post->status : $case->status;
            $stepChanged = false;
            $steps       = array();

            //---------------- Judge steps changed or not.-------------------- */

            /* Remove the empty setps in post. */
            if($this->post->steps)
            {
                foreach($this->post->steps as $key => $desc)
                {
                    $desc = trim($desc);
                    if(!empty($desc)) $steps[] = array('desc' => $desc, 'type' => $this->post->stepType[$key], 'expect' => trim($this->post->expects[$key]));
                }

                /* If step count changed, case changed. */
                if(count($case->steps) != count($steps))
                {
                    $stepChanged = true;
                }
                else
                {
                    /* Compare every step. */
                    $i = 0;
                    foreach($case->steps as $key => $oldStep)
                    {
                        if(trim($oldStep->desc) != trim($steps[$i]['desc']) or trim($oldStep->expect) != $steps[$i]['expect'] or trim($oldStep->type) != $steps[$i]['type'])
                        {
                            $stepChanged = true;
                            break;
                        }
                        $i++;
                    }
                }
            }

            if(!$this->forceNotReview() and $stepChanged) $status = 'wait';

            return array($stepChanged, $status);
        }

        return '';
    }
}
