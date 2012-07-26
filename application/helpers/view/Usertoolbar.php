<?php

/**
 * 
 * Modal overlay that contains the username and password field 
 * that is required to login in to the website
 * 
 * @author daniellevitt
 *
 */
class Helper_View_Usertoolbar extends Zend_View_Helper_Abstract {

    public function usertoolbar($noticeboards = array(), $count_users = 0, $users = array()) {
        
        $auth = Zend_Auth::getInstance()->getIdentity();
        
        ?>
        <style>
            body { padding: 86px 0 0 0}
            .navbar-fixed-top.mainnavbar { top: 36px; z-index: 1029; }
            #map-top { top: 36px; z-index: 1029; }
        </style>

        <div class="subnav subnav-fixed loggedin_subnav">
            <ul class="nav nav-pills">
                
                <li <?php if($this->view->mystream_link) { echo "class=\"active\""; } ?>><a href="<?= Zend_Registry::get('domain_name') . $this->view->url(array(), 'dashboard_home') ?>"><i class="icon-home"></i> <?= Zend_Registry::get('Zend_Translate')->_('My Stream') ?></a></li>

                <li class="dropdown dsh_bar">
                    <?php
                    $term = "noticeboards";
                    if (count($noticeboards) == 1) {
                        $term = "noticeboard";
                    }
                    ?>
                    <a href="#" class="dropdown-toggle dsh_bar_h">Following <b><?= count($noticeboards) . "</b> " . $term ?> <b class="caret"></b></a>
                    <div class="dsh_bar_c">
                        <div class="row">
                            <div class="span6">
                                <?php
                                $more = "";
                                if ($noticeboards) {
                                    $noticeboards = array_slice($noticeboards, 0, 20);
                                    foreach ($noticeboards as $l) {
                                        $name = $l['noticeboard_url'];
                                        if($l['noticeboard_name']) {
                                            $name = $l['noticeboard_name'];
                                        }
                                        echo "<a style=\"text-transform:inherit\" class=\"label label-notice dsh_n\" href=\"http://{$l['noticeboard_url']}.n0tice.com\">{$name}</a>";
                                    }
                                    echo "<hr>";
                                    echo "<a href=\"{$this->view->url(array('id' => $auth->user_login), 'user_following')}#noticeboards\" class=\"btn\">See all &raquo;</a>";
                                } else {
                                    echo "<p>You are not following any noticeboards yet.</p>";
                                }
                                ?>
                                <a href="<?= $this->view->url(array(), 'user_suggest_noticeboards') ?>" class="btn btn-primary">Find noticeboards to follow &raquo;</a>
                            </div>
                        </div>
                    </div>
                </li>
                
                <li class="dropdown dsh_bar">
                    <?php
                    $term = "users";
                    if ($count_users == 1) {
                        $term = "user";
                    }
                    ?>
                    <a href="#" class="dropdown-toggle dsh_bar_h">Following <b><?= $count_users . "</b> " . $term ?> <b class="caret"></b></a>
                    <div class="dsh_bar_c">
                        <div class="row">
                            <div class="span6">
                                <ul class="thumbs small thumbs_l_pad glossy_thumbs">
                                    <?php
                                    $more = "";
                                    if ($count_users) {
                                        foreach ($users as $l) {
                                            echo "<li>{$this->view->profilepicture($l['user_login'], 'small', 1)}</li>";
                                        }
                                        echo "<hr>";
                                    } else {
                                        echo "<p>You are not following anyone yet.</p>";
                                    }
                                    ?>
                                </ul>
                                <?php
                                if ($users) {
                                     echo "<a href=\"{$this->view->url(array('id' => $auth->user_login), 'user_following')}#users\" class=\"btn\" style=\"margin-right:10px;\">See all</a>";
                                }
                                ?>
                                <a href="<?= $this->view->url(array(), 'user_suggest_users') ?>" class="btn btn-primary">Find users to follow &raquo;</a>
                            </div>
                        </div>
                    </div>
                </li>
                
                <li class="dropdown nvb-toggle pull-right">
                    <a class="loggedin dropdown-toggle" style="background-image:url('<?= $this->view->profilepicture($auth->user_login, 'small', 'src') ?>')" href="<?= $this->view->url(array('id' => $auth->user_login), 'user_view') ?>"><?= $auth->user_login ?> <b class="caret"></b></a>
                    <?= $this->view->Dropdownprofilebox($auth) ?>
                </li>
                
                <li class="dropdown nvb-toggle pull-right">
                    <a class="dropdown-toggle not_link" href="<?= Zend_Registry::get('domain_name') . $this->view->url(array('id' => $auth->user_login), 'user_notifications') ?>"><i class="icon-globe icon-grey"></i> <span id="note_number" class="hide" style="color:white">0</span></a>
                    <?= $this->view->Dropdownnotifications() ?>
                </li>
                
            </ul>
        </div>
        <?php
    }

}
