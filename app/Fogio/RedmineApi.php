<?php namespace Fogio;

use Redmine\Client;
use stdClass;

class RedmineApi {

    protected $rm;
    protected $filter_string = '#FogBugz';
    protected $search_params = array(
        'offset'         => '0',
        'limit'          => '500',
        'sort'           => 'id',
        'project_id'     => 'kunde',
        'status_id'      => 'open',
        'cf_91808407'    => '1',
        //'created_on'     => '>=2014-05-30'
        //'cf_2'           => '1', // where 1 = id of the customer field
        //cf_SOME_CUSTOM_FIELD_ID => 'value'
    );

    function __construct($credentials)
    {
        $this->rm = new Client(
            $credentials['host'],
            $credentials['username'],
            $credentials['password']
        );
    }

    public function setSearchParams($params)
    {
        $this->search_params = $params;
    }

    private function _searchIssues()
    {
        try
        {
            $issues = $this->rm->api('issue')->all($this->search_params);
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

        return $issues;
    }

    private function _parseIssues($issues)
    {
        $_issues = new stdClass;

        $_issues->by_id       = array();
        $_issues->by_array    = array();
        $_issues->ids         = array();
        $_issues->fogbugz_ids = array();

        foreach($issues['issues'] as $issue)
        {
            $_id     = (integer) $issue['id'];
            $_title  = (string) $issue['subject'];
            //$_custom = $issue['custom_fields'];
            if(strpos($_title, $this->filter_string) === false)
            {
                continue;
            }

            $_issues->by_id[$_id]          = array();
            $_issues->by_id[$_id]['id']    = $_id;
            $_issues->by_id[$_id]['title'] = $_title;

            $_issues->by_array[] = array(
                'id'    => $_id,
                'title' => $_title,
            );

            $_issues->ids[] = $_id;

            preg_match_all('!'.$this->filter_string.' \d+:!', $_title, $matches);
            preg_match_all('!\d+!', $matches[0][0], $matches2);

            $_issues->fogbugz_ids[] = $matches2[0][0];
        }

        return $_issues;
    }

    public function getIssues()
    {
        $issues = $this->_searchIssues();

        return $this->_parseIssues($issues);
    }
    
    public function createIssues($issues)
    {
        $total_imported = 0;

        foreach($issues as $issue)
        {
            $_subject = $this->filter_string . ' ' . $issue['id'] . ': ' . $issue['title'];

            try
            {
                $this->rm->api('issue')->create(array(
                    'project_id'     => '1',
                    'subject'        => $_subject,
                    'description'    => $issue['url'],
                    'assigned_to_id' => 46, //JEL
                    'custom_fields'  => array(
                        array(
                            'id'    => 91808407,
                            'name'  => 'isFogbugz',
                            'value' => 1,
                        ),
                        array(
                            'id'    => 91808406,
                            'name'  => 'FogbugzId',
                            'value' => (integer) $issue['id'],
                        ),
                    ),
                    'watcher_user_ids' => array(46,3,50), // JEL,PAR,MAS
                ));

                $total_imported++;
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

        return $total_imported;
    }
}