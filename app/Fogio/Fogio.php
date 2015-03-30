<?php namespace Fogio;

use Fogio\FogbugzApi as Fogbugz;
use Fogio\RedmineApi as Planio;

class Fogio {

    protected $pl;
    protected $fb;

    protected $credentials;

    protected $fb_cases;
    protected $pl_issues;

    protected $issues_to_import;

    protected $fogbugz_filter = '30';
    protected $fogbugz_query = 'opened:"last week..today"';
    protected $planio_query  = array(
        'created_on'     => '>=2015-03-30',
        'limit'          => '100',
        'offset'         => '0',
        'project_id'     => 'kunde',
        'sort'           => 'id',
        'status_id'      => '*',
        'cf_91808407'    => 1,
        //'cf_2',           => '1', // where 1 = id of the customer field
        //cf_SOME_CUSTOM_FIELD_ID => 'value',
    );

    // FB => PLA
    protected $user_map = array(
        '13' => '46', // JEL
        '14' => '68', // ANK
        '8' => '50', // MAS
        '16' => '3', // PAR
        '15' => '60', // SES
    );

    function __construct($credentials)
    {
        $this->credentials = $credentials;
        $this->fb = new Fogbugz($this->credentials['fogbugz']);
        $this->pl = new Planio($this->credentials['planio']);
    }

    private function _fetchFogbugzCases()
    {
        $this->fb_cases = $this->fb->getCases();

        print sprintf(
            "Total FogBugz cases: %s\n",
            count($this->fb_cases->ids)
        );
    }

    private function _fetchPlanioIssues()
    {
        $this->pl_issues = $this->pl->getIssues();

        print sprintf(
            "Total Planio issues: %s\n",
            count($this->pl_issues->ids)
        );
    }

    public function importToPlanio()
    {
        $this->_fetchFogbugzCases();
        $this->_fetchPlanioIssues();
        $this->pl->setUserMap($this->user_map);

        $this->issues_to_import = $this->_prepareIssuesToImport();

        $total = $this->pl->createIssues($this->issues_to_import);

        if($total)
        {
            print sprintf("Total cases imported: %s\n", $total);
        }
        else
        {
            print "No new cases to import. Love <3\n";
        }
    }

    private function _prepareIssuesToImport()
    {
        $issues_to_import = array();

        foreach($this->fb_cases->ids as $case)
        {
            $found = false;

            foreach($this->pl_issues->fogbugz_ids as $issue)
            {
                if($case == $issue)
                {
                    $found = true;
                    break;
                }
            }

            if( ! $found)
            {
                $issues_to_import[] = $this->fb_cases->by_id[$case];
            }
        }

        return $issues_to_import;
    }

    public function setFogBugzFilter($filter=null)
    {
        if( ! is_null($filter)) $this->fogbugz_filter = $filter;

        $this->fb->setFilter($this->fogbugz_filter);
    }

    public function setFogBugzQuery($query=null)
    {
        if( ! is_null($query)) $this->fogbugz_query = $query;

        $this->fb->setSearchQuery($this->fogbugz_query);
    }

    public function setPlanioQuery($query=null)
    {
        if( ! is_null($query)) $this->planio_query = $query;

        $this->pl->setSearchParams($this->planio_query);
    }

}