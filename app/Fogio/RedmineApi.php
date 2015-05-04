<?php namespace Fogio;

use Redmine\Client;
use stdClass;

class RedmineApi {

    protected $rm;
    protected $rm_project_id;
    protected $rm_parent_issue_id;
    protected $filter_string = '#FogBugz';
    protected $search_params;
    protected $user_map;

    function __construct($credentials)
    {
        $this->rm = new Client(
            $credentials['host'],
            $credentials['username'],
            $credentials['password']
        );

        $this->rm_project_id = $credentials['project_id'];
        $this->rm_parent_issue_id = $credentials['parent_issue_id'];
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
            $_priority = $issue['priority'] == '1' ? 5 : 4; // 5 high (prio 1). 4 normal (all the rest)
            $_user_id = array_key_exists($issue['assigned_to'], $this->user_map) == false ? null : $this->user_map[$issue['assigned_to']];

            try
            {
                $result = $this->rm->api('issue')->create(array(
                    'project_id'     => $this->rm_project_id,
                    'subject'        => $_subject,
                    'description'    => $issue['url'],
                    'priority_id'    => $_priority,
                    'assigned_to_id' => $_user_id,
                    'due_date' => $issue['due_date'],
                    'parent_issue_id' => $this->rm_parent_issue_id,
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

                if($result->id) {
                    print $result->id . " --- " . $_subject ."\n";
                } else {
                    print "Error while creating issue: " . $_subject  ."\n";
                    print_r($result);
                }

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

    public function setUserMap($map)
    {
        $this->user_map = $map;
    }
}