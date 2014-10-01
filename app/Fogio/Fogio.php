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

    function __construct($credentials)
    {
        $this->credentials = $credentials;
    }

    private function _fetchFogbugzCases()
    {
        $this->fb = new Fogbugz($this->credentials['fogbugz']);
        $this->fb_cases = $this->fb->getCases();

        print sprintf(
            "Total FogBugz cases: %s\n",
            count($this->fb_cases->ids)
        );
    }

    private function _fetchPlanioIssues()
    {
        $this->pl = new Planio($this->credentials['planio']);
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

}