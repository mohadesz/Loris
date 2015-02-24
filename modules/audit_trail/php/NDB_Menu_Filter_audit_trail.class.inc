<?php
/**
* The Audit Trail
*
* PHP Version 5
*
* @category Main
* @package  Audit_trail
* @author   Loris Team <loris.mni@bic.mni.mcgill.ca>
* @license  http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
* @link     https://www.github.com/aces/Loris/
*/

/**
 * Implements the Audit_trail
 *
* @category Main
* @package  Audit_trail
* @author   Loris Team <loris.mni@bic.mni.mcgill.ca>
* @license  http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
* @link     https://www.github.com/aces/Loris/
 */
class NDB_Menu_Filter_audit_trail extends NDB_Menu_Filter
{
    function _hasAccess()
    {
        /*// create user object
        $user =& User::singleton();
        $site =& Site::singleton($user->getData('CenterID'));

        return (
            $user->hasPermission('access_all_profiles')
            || ($site->isStudySite() && $user->hasPermission('data_entry'))
        );
        */
        return true;
    }

    /**
     * Sets up all the class variables needed for the audit_trail menu
     * filter
     *
     * @return true on success
     */
    function _setupVariables()
    {
        // set the class variables
        $this->columns = array(
            'history.tbl', 
            'history.col',
            'history.old',
            'history.new',
            'history.changeDate',
            'history.type'
        );
        $this->query = " FROM history WHERE 1=1";
        $this->group_by = '';
        $this->order_by = 'history.tbl';
        $this->validFilters = array(
            'history.tbl', 
        );
        $this->headers = array(
            'tbl', 
            'col', 
            'old', 
            'new',
            'changeDate',
            'userID',
            'type'
        );
        $this->formToFilter = array(
            'isntrument' => 'history.tbl',
        );
        return true;
    }


    /**
     * Create the form for the audit_trail
     *
     * @return none
     */
    function _setFilterForm()
    {

        // Get instruments
        $instruments = Utility::getAllInstruments();
        $instruments = array_merge(array('' => 'All Instruments'), $instruments);
        
        // Add form elements
        $this->addSelect('Instrument', 'Instrument:', $instruments);

        return true;
    }


    /**
     * Overrides the default setDataTableRows
     *
     * @param integer $count The number of rows in the table
     *
     * @return true on success
     */
    
    function _setDataTableRows($count)
    {
        $DB =& Database::singleton();
        $x = 0;
        foreach ($this->list as $item) {
            //count column
            $this->tpl_data['items'][$x][0]['value'] = $x + $count;

            //print out data rows
            $i = 1;
            foreach ($item as $key => $val) {
                    $this->tpl_data['items'][$x][$i]['name'] = $key;
                    $this->tpl_data['items'][$x][$i]['value'] = $NewValue;
                }
            $i++;
         }
         $x++;

        return true;
    }
}
?>
