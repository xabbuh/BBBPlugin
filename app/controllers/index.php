<?php

/*
 * Copyright (C) 2012 - Till Gl�ggler     <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * @author    tgloeggl@uos.de
 * @copyright (c) Authors
 */

require_once 'app/controllers/studip_controller.php';

class IndexController extends StudipController
{
    const SALT = 'cb5dbc361d4959e27f4bfa027adc559a';
    const BBB  = 'http://bbb.virtuos.uni-osnabrueck.de/bigbluebutton/'; 
    
    private $params = array(
        'perm' => '',
        'allow_join' => false,
        'meeting_running' => false,
        'path' => ''
    );

    public function index_action()
    {
        $this->param = $this->get_params();
        
        Navigation::activateItem('course/BBBPlugin');
    }

    /**
     * creates meeting and redirects to BBB meeting. 
     */
    public function createMeeting_action() {
        $this->param = $this->get_params();

        if (!$this->param['perm'] == 'dozent') {
            $this->error();
        }

        $meetingId = Request::option('cid');
        $modPw = md5($meetingID.'modPW');
        $attPw = md5($meetingID.'attPw');
        $ret = $_SERVER['HTTP_REFERER'];
        
        $bbb = new BigBlueButton();
        $url = $bbb->createMeetingAndGetJoinURL(
                get_username($GLOBALS['user']->id), $meetingId, 'MOTD', $modPw, 
                $attPw, self::SALT, self::BBB, $ret);
        $this->redirect($url);
    }

    /**
     *  redirects to active BBB meeting. 
     */
    public function joinMeeting_action() {
        $this->param = $this->get_params();

        $meetingId = Request::option('cid');

        if ($this->param['perm'] == 'att') {
            $PW = md5($meetingID.'attPw');
        } elseif ($this->param['perm'] == 'mod') {
            $PW = md5($meetingID.'modPw');
        } else {
            $this->error();
        }

        if(!$this->param['meeting_running']) {
            $this->error();
        }
        
        $bbb = new BigBlueButton();
        $url = $bbb->joinURL($meetingId, get_username($GLOBALS['user']->id),
                $PW, self::SALT, self::BBB);
        
        $this->redirect($url);
    }

    public function meetingInfo_action($meetingId, $moderatorPw) {
        return true;
        // get details about a currently running meeting
    }
    
    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * * * H E L P E R   F U N C T I O N S * * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    
    /**
     * Initiate plugin params:
     * 
     * 'perm'           => 'mod' has BBB moderator permission
     *                     'att' has BBB attendee permission 
     * 'allow_join      => true if user is allowed to join
     * 'meeting_running'=> true if meeting is running
     * 'path'           => relative path to plugin
     */

    private function get_params()
    {
        if ($GLOBALS['perm']->have_studip_perm("dozent", $this->getId())) {
            $params['perm'] = 'mod';
        } elseif ($GLOBALS['perm']->have_studip_perm("autor", $this->getId())) {
            $params['perm'] = 'att';
        }

        if ($params['perm'] !== '') {
            $params['allow_join'] = true;
        }
        
        $bbb = new BigBlueButton();
        $meetingId = Request::option('cid');
        $params['meeting_running'] = $bbb->isMeetingRunning($meetingId, self::BBB, self::SALT);
        
        return $params;
    }

    function getId()
    {
        if (!Request::option('cid')) {
            if ($GLOBALS['SessionSeminar']) {
                URLHelper::bindLinkParam('cid', $GLOBALS['SessionSeminar']);
                return $GLOBALS['SessionSeminar'];
            }

            return false;
        }

        return Request::option('cid');
    }

    /**
     * Common code for all actions: set default layout and page title.
     *
     * @param type $action
     * @param type $args
     */
    function before_filter(&$action, &$args)
    {
        $this->validate_args($args, array('option', 'option'));

        parent::before_filter($action, $args);

        $this->flash = Trails_Flash::instance();

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        PageLayout::setTitle(getHeaderLine($this->getId()) .' - '. _('Big Blue Button'));

        if ($GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] && $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] != '/') {
            $this->picturepath = $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] .'/'. $this->dispatcher->trails_root . '/images';
        } else {
            $this->picturepath = '/'. $this->dispatcher->trails_root . '/images';
        }
    }
}