<?php
/**
 * The user module zh-tw file of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青島易軟天創網絡科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     user
 * @version     $Id: zh-tw.php 5053 2013-07-06 08:17:37Z wyd621@gmail.com $
 * @link        http://www.zentao.net
 */
$lang->user->common           = '用戶';
$lang->user->id               = '用戶編號';
$lang->user->company          = '所屬公司';
$lang->user->dept             = '所屬部門';
$lang->user->account          = '用戶名';
$lang->user->password         = '密碼';
$lang->user->password2        = '請重複密碼';
$lang->user->role             = '職位';
$lang->user->group            = '權限分組';
$lang->user->realname         = '真實姓名';
$lang->user->nickname         = '暱稱';
$lang->user->commiter         = '原始碼帳號';
$lang->user->birthyear        = '出生年';
$lang->user->gender           = '性別';
$lang->user->email            = '郵箱';
$lang->user->basicInfo        = '基本信息';
$lang->user->accountInfo      = '帳號信息';
$lang->user->verify           = '安全驗證';
$lang->user->contactInfo      = '聯繫信息';
$lang->user->skype            = 'Skype';
$lang->user->qq               = 'QQ';
$lang->user->mobile           = '手機';
$lang->user->phone            = '電話';
$lang->user->weixin           = '微信';
$lang->user->dingding         = '釘釘';
$lang->user->slack            = 'Slack';
$lang->user->whatsapp         = 'WhatsApp';
$lang->user->address          = '通訊地址';
$lang->user->zipcode          = '郵編';
$lang->user->join             = '入職日期';
$lang->user->visits           = '訪問次數';
$lang->user->ip               = '最後IP';
$lang->user->last             = '最後登錄';
$lang->user->ranzhi           = '然之帳號';
$lang->user->ditto            = '同上';
$lang->user->originalPassword = '原密碼';
$lang->user->newPassword      = '新密碼';
$lang->user->verifyPassword   = '您的密碼';
$lang->user->resetPassword    = '忘記密碼';
$lang->user->score            = '積分';

$lang->user->legendBasic        = '基本資料';
$lang->user->legendContribution = '個人貢獻';

$lang->user->index         = "用戶視圖首頁";
$lang->user->view          = "用戶詳情";
$lang->user->create        = "添加用戶";
$lang->user->batchCreate   = "批量添加用戶";
$lang->user->edit          = "編輯用戶";
$lang->user->batchEdit     = "批量編輯";
$lang->user->unlock        = "解鎖用戶";
$lang->user->delete        = "刪除用戶";
$lang->user->unbind        = "解除然之綁定";
$lang->user->login         = "用戶登錄";
$lang->user->mobileLogin   = "手機訪問";
$lang->user->editProfile   = "修改檔案";
$lang->user->deny          = "訪問受限";
$lang->user->confirmDelete = "您確定刪除該用戶嗎？";
$lang->user->confirmUnlock = "您確定解除該用戶的鎖定狀態嗎？";
$lang->user->confirmUnbind = "您確定解除該用戶跟然之的綁定嗎？";
$lang->user->relogin       = "重新登錄";
$lang->user->asGuest       = "遊客訪問";
$lang->user->goback        = "返回前一頁";
$lang->user->deleted       = '(已刪除)';
$lang->user->search        = '搜索';

$lang->user->saveTemplate          = '保存模板';
$lang->user->setPublic             = '設為公共模板';
$lang->user->deleteTemplate        = '刪除模板';
$lang->user->setTemplateTitle      = '請輸入模板標題';
$lang->user->applyTemplate         = '應用模板';
$lang->user->confirmDeleteTemplate = '您確認要刪除該模板嗎？';
$lang->user->setPublicTemplate     = '設為公共模板';
$lang->user->tplContentNotEmpty    = '模板內容不能為空!';

$lang->user->profile     = '檔案';
$lang->user->project     = $lang->projectCommon;
$lang->user->task        = '任務';
$lang->user->bug         = 'Bug';
$lang->user->test        = '測試';
$lang->user->testTask    = '測試任務';
$lang->user->testCase    = '測試用例';
$lang->user->schedule    = '日程';
$lang->user->todo        = '待辦';
$lang->user->story       = '需求';
$lang->user->dynamic     = '動態';

$lang->user->openedBy    = '由%s創建';
$lang->user->assignedTo  = '指派給%s';
$lang->user->finishedBy  = '由%s完成';
$lang->user->resolvedBy  = '由%s解決';
$lang->user->closedBy    = '由%s關閉';
$lang->user->reviewedBy  = '由%s評審';
$lang->user->canceledBy  = '由%s取消';

$lang->user->testTask2Him = '%s負責的版本';
$lang->user->case2Him     = '給%s的用例';
$lang->user->caseByHim    = '%s建的用例';

$lang->user->errorDeny    = "抱歉，您無權訪問『<b>%s</b>』模組的『<b>%s</b>』功能。請聯繫管理員獲取權限。點擊後退返回上頁。";
$lang->user->errorView    = "抱歉，您無權訪問『<b>%s</b>』視圖。請聯繫管理員獲取權限。點擊後退返回上頁。";
$lang->user->loginFailed  = "登錄失敗，請檢查您的用戶名或密碼是否填寫正確。";
$lang->user->lockWarning  = "您還有%s次嘗試機會。";
$lang->user->loginLocked  = "密碼嘗試次數太多，請聯繫管理員解鎖，或%s分鐘後重試。";
$lang->user->weakPassword = "您的密碼強度小於系統設定。";
$lang->user->errorWeak    = "密碼不能使用【%s】這些常用弱口令。";

$lang->user->roleList['']       = '';
$lang->user->roleList['dev']    = '研發';
$lang->user->roleList['qa']     = '測試';
$lang->user->roleList['pm']     = '項目經理';
$lang->user->roleList['po']     = '產品經理';
$lang->user->roleList['td']     = '研發主管';
$lang->user->roleList['pd']     = '產品主管';
$lang->user->roleList['qd']     = '測試主管';
$lang->user->roleList['top']    = '高層管理';
$lang->user->roleList['others'] = '其他';

$lang->user->genderList['m'] = '男';
$lang->user->genderList['f'] = '女';

$lang->user->thirdPerson['m'] = '他';
$lang->user->thirdPerson['f'] = '她';

$lang->user->passwordStrengthList[0] = "<span style='color:red'>弱</span>";
$lang->user->passwordStrengthList[1] = "<span style='color:#000'>中</span>";
$lang->user->passwordStrengthList[2] = "<span style='color:green'>強</span>";

$lang->user->statusList['active'] = '正常';
$lang->user->statusList['delete'] = '刪除';

$lang->user->personalData['createdTodo']  = '創建的待辦數';
$lang->user->personalData['createdStory'] = '創建的需求數';
$lang->user->personalData['finishedTask'] = '完成的任務數';
$lang->user->personalData['resolvedBug']  = '解決的Bug數';
$lang->user->personalData['createdCase']  = '創建的用例數';

$lang->user->keepLogin['on']      = '保持登錄';
$lang->user->loginWithDemoUser    = '使用demo帳號登錄：';

$lang->user->tpl = new stdclass();
$lang->user->tpl->type    = '類型';
$lang->user->tpl->title   = '模板名';
$lang->user->tpl->content = '內容';
$lang->user->tpl->public  = '是否公開';

$lang->user->placeholder = new stdclass();
$lang->user->placeholder->account     = '英文、數字和下劃線的組合，三位以上';
$lang->user->placeholder->password1   = '六位以上';
$lang->user->placeholder->role        = '職位影響內容和用戶列表的順序。';
$lang->user->placeholder->group       = '分組決定用戶的權限列表。';
$lang->user->placeholder->commiter    = '版本控制系統(subversion)中的帳號';
$lang->user->placeholder->verify      = '請輸入您的系統登錄密碼';

$lang->user->placeholder->passwordStrength[1] = '6位以上，包含大小寫字母，數字。';
$lang->user->placeholder->passwordStrength[2] = '10位以上，包含大小寫字母，數字，特殊字元。';

$lang->user->error = new stdclass();
$lang->user->error->account        = "【ID %s】的用戶名應該為：三位以上的英文、數字或下劃線的組合";
$lang->user->error->accountDupl    = "【ID %s】的用戶名已經存在";
$lang->user->error->realname       = "【ID %s】的真實姓名必須填寫";
$lang->user->error->password       = "【ID %s】的密碼必須為六位以上";
$lang->user->error->mail           = "【ID %s】的郵箱地址不正確";
$lang->user->error->reserved       = "【ID %s】的用戶名已被系統預留";
$lang->user->error->weakPassword   = "【ID %s】的密碼強度小於系統設定。";
$lang->user->error->dangerPassword = "【ID %s】的密碼不能使用【%s】這些常用若口令。";

$lang->user->error->verifyPassword   = "驗證失敗，請檢查您的系統登錄密碼是否正確";
$lang->user->error->originalPassword = "原密碼不正確";

$lang->user->contactFieldList['phone']    = $lang->user->phone;
$lang->user->contactFieldList['mobile']   = $lang->user->mobile;
$lang->user->contactFieldList['qq']       = $lang->user->qq;
$lang->user->contactFieldList['dingding'] = $lang->user->dingding;
$lang->user->contactFieldList['weixin']   = $lang->user->weixin;
$lang->user->contactFieldList['skype']    = $lang->user->skype;
$lang->user->contactFieldList['slack']    = $lang->user->slack;
$lang->user->contactFieldList['whatsapp'] = $lang->user->whatsapp;

$lang->user->contacts = new stdclass();
$lang->user->contacts->common   = '聯繫人';
$lang->user->contacts->listName = '列表名稱';
$lang->user->contacts->userList = '用戶列表';

$lang->user->contacts->manage        = '維護列表';
$lang->user->contacts->contactsList  = '已有列表';
$lang->user->contacts->selectedUsers = '選擇用戶';
$lang->user->contacts->selectList    = '選擇列表';
$lang->user->contacts->createList    = '創建新列表';
$lang->user->contacts->noListYet     = '還沒有創建任何列表，請先創建聯繫人列表。';
$lang->user->contacts->confirmDelete = '您確定要刪除這個列表嗎？';
$lang->user->contacts->or            = ' 或者 ';

$lang->user->resetFail       = "重置密碼失敗，檢查用戶名是否存在！";
$lang->user->resetSuccess    = "重置密碼成功，請用新密碼登錄。";
$lang->user->noticeResetFile = "<h5>普通用戶請聯繫管理員重置密碼</h5>
    <h5>管理員請登錄禪道所在的伺服器，創建<span> '%s' </span>檔案。</h5>
    <p>注意：</p>
    <ol>
    <li>檔案內容為空。</li>
    <li>如果之前檔案存在，刪除之後重新創建。</li>
    </ol>"; 
