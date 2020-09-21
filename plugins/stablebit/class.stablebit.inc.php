<?php
/**
 * StableBit Plugin, which displays disks state
 *
 * @category  PHP
 * @package   PSI_Plugin_StableBit
 * @author    Mieczyslaw Nalewaj <namiltd@users.sourceforge.net>
 * @copyright 2017 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License version 2, or (at your option) any later version
 * @version   $Id: class.stablebit.inc.php 661 2012-08-27 11:26:39Z namiltd $
 * @link      http://phpsysinfo.sourceforge.net
 */
class StableBit extends PSI_Plugin
{
    /**
     * variable, which holds the result before the xml is generated out of this array
     * @var array
     */
    private $_result;

    /**
     * read the data into an internal array and also call the parent constructor
     *
     * @param String $enc encoding
     */
    public function __construct($enc)
    {
        parent::__construct(__CLASS__, $enc);

        $this->_result = array();
    }

    private $stablebit_items = array('Name', 'Firmware', 'Size', 'TemperatureC', 'PowerState', 'IsHot', 'IsSmartWarning', 'IsSmartPastThresholds', 'IsSmartPastAdvisoryThresholds', 'IsSmartFailurePredicted', 'IsDamaged', 'SerialNumber');

    /**
     * doing all tasks to get the required informations that the plugin needs
     * result is stored in an internal array
     *
     * @return void
     */
    public function execute()
    {
        try {
            if (PSI_OS === "Linux") {
                if (defined('PSI_PLUGIN_STABLEBIT_WMI_HOSTNAME'))
                    $wmi = '--namespace="root\StableBit\Scanner" -U '.PSI_PLUGIN_STABLEBIT_WMI_USER.'%'.PSI_PLUGIN_STABLEBIT_WMI_PASSWORD.' //'.PSI_PLUGIN_STABLEBIT_WMI_HOSTNAME.' "select * from';
                elseif (defined('PSI_WMI_HOSTNAME'))
                    $wmi = '--namespace="root\StableBit\Scanner" -U '.PSI_WMI_USER.'%'.PSI_WMI_PASSWORD.' //'.PSI_WMI_HOSTNAME.' "select * from';
                else
                    $wmi = null;
            } elseif (PHP_OS === "WINNT") {
                $objLocator = new COM('WbemScripting.SWbemLocator');
                if (defined('PSI_PLUGIN_STABLEBIT_WMI_HOSTNAME'))
                    $wmi = $objLocator->ConnectServer(PSI_PLUGIN_STABLEBIT_WMI_HOSTNAME, 'root\StableBit\Scanner', PSI_PLUGIN_STABLEBIT_WMI_USER, PSI_PLUGIN_STABLEBIT_WMI_PASSWORD);
                elseif (defined('PSI_WMI_HOSTNAME'))
                    $wmi = $objLocator->ConnectServer(PSI_WMI_HOSTNAME, 'root\StableBit\Scanner', PSI_WMI_USER, PSI_WMI_PASSWORD);
                else
                    $wmi = $objLocator->ConnectServer('', 'root\StableBit\Scanner');
            } else {
                $wmi = null;
            }
            $this->_result = CommonFunctions::getWMI($wmi, 'Disks', $this->stablebit_items);
        } catch (Exception $e) {
        }
    }

   /**
     * generates the XML content for the plugin
     *
     * @return SimpleXMLElement entire XML content for the plugin
     */
    public function xml()
    {
        foreach ($this->_result as $disk_items) {
            if (isset($disk_items['Name']) && (trim($disk_items['Name']) !== '')) {
                $xmlstablebit_disk = $this->xml->addChild("Disk");
                foreach ($this->stablebit_items as $item) {
                    if (isset($disk_items[$item]) && (($itemvalue=$disk_items[$item]) !== '') &&
                    (($item !== 'SerialNumber') || (defined('PSI_PLUGIN_STABLEBIT_SHOW_SERIAL') && (PSI_PLUGIN_STABLEBIT_SHOW_SERIAL === true)))) {
                        $xmlstablebit_disk ->addAttribute($item, $itemvalue);
                    }
                }
            }
        }

        return $this->xml->getSimpleXmlElement();
    }
}
