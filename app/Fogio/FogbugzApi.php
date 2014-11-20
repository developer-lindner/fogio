<?php namespace Fogio;

use stdClass;
use There4\FogBugz;

class FogbugzApi {

    protected $fb;
    protected $filter       = 15; //All open cases by Id
    protected $columns      = 'ixBug,sTitle,dtOpened,ixBugParent,ixPriority';
    protected $search_query = 'opened:"yesterday..today"';
    protected $host;

    function __construct($credentials)
    {
        $this->fb = new FogBugz\Api(
            $credentials['username'],
            $credentials['password'],
            $credentials['host']
        );

        $this->host = $credentials['host'];
    }

    public function login()
    {
        try
        {
            $this->fb->logon();
        }
        catch (Exception $e)
        {
            print sprintf(
                "Error: [Code %d] %s\n",
                $e->getCode(),
                $e->getMessage()
            );

            exit(1);
        }
    }

    public function logout()
    {
        try
        {
            $this->fb->logoff();
        }
        catch (Exception $e)
        {
            print sprintf(
                "Error: [Code %d] %s\n",
                $e->getCode(),
                $e->getMessage()
            );

            exit(1);
        }
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    public function setColumns($columns)
    {
        $this->columns = $columns;
    }

    public function setSearchQuery($q)
    {
        $this->search_query = $q;
    }

    private function _setFilter()
    {
        try
        {
            $this->fb->setCurrentFilter(array('sFilter' => $this->filter));
        }
        catch (Exception $e)
        {
            print sprintf(
                "Error: [Code %d] %s\n",
                $e->getCode(),
                $e->getMessage()
            );

            exit(1);
        }
    }

    private function _searchCases()
    {
        try
        {
            $cases = $this->fb->search(array(
                'cols' => $this->columns,
                'q' => $this->search_query
            ));
        }
        catch (Exception $e)
        {
            print sprintf(
                "Error: [Code %d] %s\n",
                $e->getCode(),
                $e->getMessage()
            );

            exit(1);
        }

        return $cases;
    }

    public function _parseCases($cases)
    {
        $_cases = new stdClass;

        $_cases->by_id    = array();
        $_cases->by_array = array();
        $_cases->ids      = array();

        foreach($cases->cases->children() as $case)
        {
            $_id    = (integer) $case['ixBug'];
            $_title = (string) $case->sTitle;
            $_priority = (string) $case->ixPriority;
            $_link  = $this->host."/f/cases/".$_id;

            $_cases->by_id[$_id]          = array();
            $_cases->by_id[$_id]['id']    = $_id;
            $_cases->by_id[$_id]['title'] = $_title;
            $_cases->by_id[$_id]['priority']   = $_priority;
            $_cases->by_id[$_id]['url']   = $_link;

            $_cases->by_array[] = array(
                'id'    => $_id,
                'title' => $_title,
                'priority' => $_priority,
                'url'   => $_link
            );

            $_cases->ids[] = $_id;
        }

        return $_cases;
    }

    public function getCases()
    {
        $this->login();
        $this->_setFilter();
        $cases = $this->_searchCases();

        return $this->_parseCases($cases);
    }

} 