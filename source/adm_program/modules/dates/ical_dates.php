<?php
/******************************************************************************
 * ical - Feed fuer Termine
 *
 * Copyright    : (c) 2004 - 2012 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Erzeugt einen RSS 2.0 - Feed mit Hilfe der RSS-Klasse fuer die 10 naechsten Termine
 *
 * Spezifikation von RSS 2.0: http://www.feedvalidator.org/docs/rss2.html
 *
 * Parameters:
 *
 * headline: - Ueberschrift fuer den Ics-Feed
 *             (Default) Termine
 * mode:     1 - Textausgabe
 *           2 - Download
 * cat_id    - show all dates of calendar with this id
 *
 *****************************************************************************/

require_once('../../system/common.php');
require_once('../../system/classes/table_date.php');
require_once('../../system/classes/table_category.php');
require_once('../../system/classes/table_rooms.php');
require_once('../../system/classes/module_dates.php');
unset($_SESSION['dates_request']);

// prüfen ob das Modul überhaupt aktiviert ist
if($gPreferences['enable_dates_module'] == 0)
{
    // das Modul ist deaktiviert
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
}
elseif($gPreferences['enable_dates_module'] == 2)
{
    // nur eingelochte Benutzer dürfen auf das Modul zugreifen
    require_once('../../system/login_valid.php');
}

// Nachschauen ob ical ueberhaupt aktiviert ist bzw. das Modul oeffentlich zugaenglich ist
if ($gPreferences['enable_dates_ical'] != 1)
{
    $gMessage->setForwardUrl($gHomepage);
    $gMessage->show($gL10n->get('SYS_ICAL_DISABLED'));
}

// Initialize and check the parameters
$getHeadline = admFuncVariableIsValid($_GET, 'headline', 'string', $gL10n->get('DAT_DATES'));
$getMode   = admFuncVariableIsValid($_GET, 'mode', 'numeric', 2);
$getCatId    = admFuncVariableIsValid($_GET, 'cat_id', 'numeric', 0);


//create Object
$dates = new Dates();

//Headline für Dateinamen
$headline = $getHeadline;
if($getCatId > 0)
{
    $calendar = new TableCategory($gDb, $getCatId);
    $headline.= '_'. $calendar->getValue('cat_name');
    
    //Set Category
    $dates->setCatId($getCatId);
}

//Set Period
$dates->setMode('period',date('Y-m-d',time()-$gPreferences['dates_ical_days_past']*86400),date('Y-m-d',time()+$gPreferences['dates_ical_days_future']*86400));

// read events for output
$datesResult = $dates->getDates();

$date = new TableDate($gDb);
$iCal = $date->getIcalHeader();

if($datesResult['numResults'] > 0)
{
    $date = new TableDate($gDb);
    foreach($datesResult['dates'] as $row)
    {
        $date->clear();
        $date->setArray($row);
        $iCal .= $date->getIcalVEvent($_SERVER['HTTP_HOST']);
    }
}

$iCal .= $date->getIcalFooter();

if($getMode == 2)
{
    header('Content-Type: text/calendar');

    if (preg_match("/MSIE/", $_SERVER["HTTP_USER_AGENT"]))
    {
        header('Content-Disposition: attachment; filename='. urlencode($headline). '.ics');
        // noetig fuer IE, da ansonsten der Download mit SSL nicht funktioniert
    }
    else
    {
        header('Content-Disposition: attachment; filename='. $headline. '.ics');
    }
    
    header('Cache-Control: private');
    header('Pragma: public');
}    
echo $iCal;
?>