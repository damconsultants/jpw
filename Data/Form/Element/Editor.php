<?php
/**
 * DamConsultants
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 *  DamConsultants_JPW
 */
namespace DamConsultants\JPW\Data\Form\Element;

use Magento\Framework\Escaper;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class Editor extends \Magento\Framework\Data\Form\Element\Editor
{

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Editor constructor.
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $data
     * @throws \RuntimeException
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);

        if ($this->isEnabled()) {
            $this->setType('wysiwyg');
            $this->setExtType('wysiwyg');
        } else {
            $this->setType('textarea');
            $this->setExtType('textarea');
        }
        $this->serializer = $serializer ?:
            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get Configs
     *
     * @param string $path
     */
    public function getConfigs($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Returns buttons translation
     *
     * @return array
     */
    protected function getButtonTranslations()
    {
        $buttonTranslations = [
            'Insert Image...' => $this->translate('Insert Image...'),
            'Insert Media...' => $this->translate('Insert Media...'),
            'Insert File...' => $this->translate('Insert File...'),
            'Bynder...' => $this->translate('Bynder...'),
        ];
        return $buttonTranslations;
    }

    /**
     * Returns JS config
     *
     * @return bool|string
     * @throws \InvalidArgumentException
     */
    protected function getJsonConfig()
    {
        if (is_object($this->getConfig())
            && method_exists($this->getConfig(), 'toJson')
        ) {
            return $this->getConfig()->toJson();
        } else {
            return $this->serializer->serialize(
                $this->getConfig()
            );
        }
    }

    /**
     * Fetch config options from plugin.
     *
     * @param string $pluginName
     * @param string|null $key
     * @return mixed all options or single option if $key is passed; null if nonexistent
     */
    public function getPluginConfigOptions($pluginName, $key = null)
    {
        if (!is_array($this->getConfig('plugins'))) {
            return null;
        }

        $plugins = $this->getConfig('plugins');

        $pluginArrIndex = array_search($pluginName, array_column($plugins, 'name'));

        if ($pluginArrIndex === false || !isset($plugins[$pluginArrIndex]['options'])) {
            return null;
        }

        $pluginOptions = $plugins[$pluginArrIndex]['options'];

        if ($key !== null) {
            return $pluginOptions[$key] ?? null;
        } else {
            return $pluginOptions;
        }
    }

    /**
     * Returns element html
     *
     * @return string
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getElementHtml()
    {
        $js = '
            <script type="text/javascript">
            //<![CDATA[
                openEditorPopup = function(url, name, specs, parent) {
                    if ((typeof popups == "undefined") || popups[name] == undefined || popups[name].closed) {
                        if (typeof popups == "undefined") {
                            popups = new Array();
                        }
                        var opener = (parent != undefined ? parent : window);
                        popups[name] = opener.open(url, name, specs);
                    } else {
                        popups[name].focus();
                    }
                    return popups[name];
                }

                closeEditorPopup = function(name) {
                    if ((typeof popups != "undefined") && popups[name] != undefined && !popups[name].closed) {
                        popups[name].close();
                    }
                }
            //]]>
            </script>';

        if ($this->isEnabled()) {
            $jsSetupObject = 'wysiwyg' . $this->getHtmlId();

            $forceLoad = '';
            if (!$this->isHidden()) {
                if ($this->getForceLoad()) {
                    $forceLoad = $jsSetupObject . '.setup("exact");';
                } else {
                    $forceLoad = 'jQuery(window).on("load", ' .
                            $jsSetupObject .
                            '.setup.bind(' .
                            $jsSetupObject .
                            ', "exact"));';
                }
            }

            $html = $this->_getButtonsHtml() .
                    '<textarea name="' .
                    $this->getName() .
                    '" title="' .
                    $this->getTitle() .
                    '" ' .
                    $this->_getUiId() .
                    ' id="' .
                    $this->getHtmlId() .
                    '"' .
                    ' class="textarea' .
                    $this->getClass() .
                    '" ' .
                    $this->serialize(
                        $this->getHtmlAttributes()
                    ) .
                    ' >' .
                    $this->getEscapedValue() .
                    '</textarea>' .
                    $js . $this->getInlineJs($jsSetupObject, $forceLoad);

            $html = $this->_wrapIntoContainer($html);
            $html .= $this->getAfterElementHtml();
            return $html;
        } else {
            /* Display only buttons to additional features */
            if ($this->getPluginConfigOptions('magentowidget', 'window_url')) {
                $html = $this->_getButtonsHtml() . $js . parent::getElementHtml();
                if ($this->getConfig('add_widgets')) {
                    $html .= '<script type="text/javascript">
                    //<![CDATA[
                    require(["jquery", "mage/translate", "mage/adminhtml/wysiwyg/widget"], function(jQuery){
                        (function($) {
                            $.mage.translate.add(' . $this->serializer->serialize($this->getButtonTranslations()) . ')
                        })(jQuery);
                    });
                    //]]>
                    </script>';
                }
                $html = $this->_wrapIntoContainer($html);
                return $html;
            }
            return parent::getElementHtml();
        }
    }

    /**
     * Returns theme
     *
     * @return mixed
     */
    public function getTheme()
    {
        if (!$this->hasData('theme')) {
            return 'simple';
        }

        return $this->_getData('theme');
    }

    /**
     * Return Editor top Buttons HTML
     *
     * @return string
     */
    protected function _getButtonsHtml()
    {
        $buttonsHtml = '<div id="buttons' . $this->getHtmlId() . '" class="buttons-set">';
        if ($this->isEnabled()) {
            $buttonsHtml .= $this->_getToggleButtonHtml($this->isToggleButtonVisible());
            $buttonsHtml .= $this->_getPluginButtonsHtml($this->isHidden());
        } else {
            $buttonsHtml .= $this->_getPluginButtonsHtml(true);
        }
        $buttonsHtml .= '</div>';

        return $buttonsHtml;
    }

    /**
     * Return HTML button to toggling WYSIWYG
     *
     * @param bool $visible
     * @return string
     */
    protected function _getToggleButtonHtml($visible = true)
    {
        $html = $this->_getButtonHtml(
            [
                    'title' => $this->translate('Show / Hide Editor...'),
                    'class' => 'action-show-hide',
                    'style' => $visible ? '' : 'display:none',
                    'id' => 'toggle' . $this->getHtmlId(),
                ]
        );
        return $html;
    }

    /**
     * Prepare Html buttons for additional WYSIWYG features
     *
     * @param bool $visible Display button or not
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getPluginButtonsHtml($visible = true)
    {
        $buttonsHtml = '';

        /* Button to widget insertion window */
        if ($this->getConfig('add_widgets')) {
            $buttonsHtml .= $this->_getButtonHtml(
                [
                        'title' => $this->translate('Insert Widget...'),
                        'onclick' => "widgetTools.openDialog('"
                        . $this->getPluginConfigOptions('magentowidget', 'window_url')
                        . "widget_target_id/" . $this->getHtmlId() . "/')",
                        'class' => 'action-add-widget plugin',
                        'style' => $visible ? '' : 'display:none',
                    ]
            );
        }

        /* Button to media images insertion window */
        if ($this->getConfig('add_images')) {
            $buttonsHtml .= $this->_getButtonHtml(
                [
                        'title' => $this->translate('Insert Image...'),
                        'onclick' => "MediabrowserUtility.openDialog('"
                        . $this->getConfig('files_browser_window_url')
                        . "target_element_id/" . $this->getHtmlId() . "/"
                        . (null !== $this->getConfig('store_id') ? 'store/'
                        . $this->getConfig('store_id') . '/' : '')
                        . "')",
                        'class' => 'action-add-image plugin',
                        'style' => $visible ? '' : 'display:none',
                    ]
            );
        }

        if (is_array($this->getConfig('plugins'))) {
            foreach ($this->getConfig('plugins') as $plugin) {
                if (isset($plugin['options']) && $this->_checkPluginButtonOptions($plugin['options'])) {
                    $buttonOptions = $this->_prepareButtonOptions($plugin['options']);
                    if (!$visible) {
                        $configStyle = '';
                        if (isset($buttonOptions['style'])) {
                            $configStyle = $buttonOptions['style'];
                        }
                        $buttonOptions['style'] = 'display:none; ' . $configStyle;
                    }
                    $buttonsHtml .= $this->_getButtonHtml($buttonOptions);
                }
            }
        }

        /* Button to media bynder insertion window */
        if ($this->getConfig('add_images')) {
            $x = $this->getConfigs("web/secure/base_url");
            $buttonsHtml .= $this->_getButtonHtml(
                [
                        'title' => $this->translate('Bynder Media...'),
                        'onclick' => "javascript:void(0)",
                        'class' => 'action-add-widget plugin cms_bynder_action_btn ' . $this->getBaseUrl(),
                        'id' => "cms_bynder_action",
                        'style' => $visible ? '' : 'display:none',
                        'name' => $x,
                    ]
            );
        }
        return $buttonsHtml;
    }
    /**
     * Get Base Url
     */
    public function getBaseUrl()
    {
        $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        /* get Store Url without index.php */
        $storeUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        return $storeUrl;
    }

    /**
     * Prepare button options array to create button html
     *
     * @param array $options
     * @return array
     */
    protected function _prepareButtonOptions($options)
    {
        $buttonOptions = [];
        $buttonOptions['class'] = 'plugin';
        foreach ($options as $name => $value) {
            $buttonOptions[$name] = $value;
        }
        $buttonOptions = $this->_prepareOptions($buttonOptions);
        return $buttonOptions;
    }

    /**
     * Check if plugin button options have required values
     *
     * @param array $pluginOptions
     * @return boolean
     */
    protected function _checkPluginButtonOptions($pluginOptions)
    {
        if (!isset($pluginOptions['title'])) {
            return false;
        }
        return true;
    }

    /**
     * Convert options
     *
     * Convert options by replacing template constructions ( like {{var_name}} )
     * with data from this element object
     *
     * @param array $options
     * @return array
     */
    protected function _prepareOptions($options)
    {
        $preparedOptions = [];
        foreach ($options as $name => $value) {
            if (is_array($value) && isset($value['search']) && isset($value['subject'])) {
                $subject = $value['subject'];
                foreach ($value['search'] as $part) {
                    $subject = str_replace('{{' . $part . '}}', $this->getDataUsingMethod($part), $subject);
                }
                $preparedOptions[$name] = $subject;
            } else {
                $preparedOptions[$name] = $value;
            }
        }
        return $preparedOptions;
    }

    /**
     * Return custom button HTML
     *
     * @param array $data Button params
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getButtonHtml($data)
    {
        $html = '<button type="button"';
        $html .= ' class="scalable ' . (isset($data['class']) ? $data['class'] : '') . '"';
        $html .= isset($data['onclick']) ? ' onclick="' . $data['onclick'] . '"' : '';
        $html .= isset($data['style']) ? ' style="' . $data['style'] . '"' : '';
        $html .= isset($data['id']) ? ' id="' . $data['id'] . '"' : '';
        $html .= '>';
        $html .= isset($data['title']) ? '<span><span><span>' . $data['title'] . '</span></span></span>' : '';
        $html .= '</button>';

        return $html;
    }

    /**
     * Wraps Editor HTML into div if 'use_container' config option is set to true
     *
     * If 'no_display' config option is set to true, the div will be invisible
     *
     * @param string $html HTML code to wrap
     * @return string
     */
    protected function _wrapIntoContainer($html)
    {
        if (!$this->getConfig('use_container')) {
            return '<div class="admin__control-wysiwig">' . $html . '</div>';
        }

        $html = '<div id="editor' . $this->getHtmlId() . '"'
                . ($this->getConfig('no_display') ? ' style="display:none;"' : '')
                . ($this->getConfig('container_class') ? ' class="admin__control-wysiwig '
                . $this->getConfig('container_class') . '"' : '')
                . '>' . $html . '</div>';

        return $html;
    }

    /**
     * Editor config retriever
     *
     * @param string $key Config var key
     * @return mixed
     */
    public function getConfig($key = null)
    {
        if (!$this->_getData('config') instanceof \Magento\Framework\DataObject) {
            $config = new \Magento\Framework\DataObject();
            $this->setConfig($config);
        }
        if ($key !== null) {
            return $this->_getData('config')->getData($key);
        }
        return $this->_getData('config');
    }

    /**
     * Translate string using defined helper
     *
     * @param string $string String to be translated
     * @return \Magento\Framework\Phrase
     */
    public function translate($string)
    {
        return (string) new \Magento\Framework\Phrase($string);
    }

    /**
     * Check whether Wysiwyg is enabled or not
     *
     * @return bool
     */
    public function isEnabled()
    {
        $result = false;
        if ($this->getConfig('enabled')) {
            $result = $this->hasData('wysiwyg') ? $result = $this->getWysiwyg() : true;
        }
        return $result;
    }

    /**
     * Check whether Wysiwyg is loaded on demand or not
     *
     * @return bool
     */
    public function isHidden()
    {
        return $this->getConfig('hidden');
    }

    /**
     * Is Toggle Button Visible
     *
     * @return bool
     */
    protected function isToggleButtonVisible()
    {
        return !$this->getConfig()->hasData(
            'toggle_button'
        ) || $this->getConfig('toggle_button');
    }

    /**
     * Returns inline js to initialize wysiwyg adapter
     *
     * @param string $jsSetupObject
     * @param string $forceLoad
     * @return string
     */
    protected function getInlineJs($jsSetupObject, $forceLoad)
    {
        $jsString = '
                <script type="text/javascript">
                //<![CDATA[
                window.tinyMCE_GZ = window.tinyMCE_GZ || {};
                window.tinyMCE_GZ.loaded = true;
                require([
                "jquery",
                "mage/translate",
                "mage/adminhtml/events",
                "mage/adminhtml/wysiwyg/tiny_mce/setup",
                "mage/adminhtml/wysiwyg/widget",
				"bynderjs"
                ], function(jQuery){' .
                "\n" .
                '  (function($) {$.mage.translate.add(' .
                $this->serializer->serialize(
                    $this->getButtonTranslations()
                ) .
                ')})(jQuery);' .
                "\n" .
                $jsSetupObject .
                ' = new wysiwygSetup("' .
                $this->getHtmlId() .
                '", ' .
                $this->getJsonConfig() .
                ');' .
                $forceLoad .
                '
                    editorFormValidationHandler = ' .
                $jsSetupObject .
                '.onFormValidation.bind(' .
                $jsSetupObject .
                ');
                    Event.observe("toggle' .
                $this->getHtmlId() .
                '", "click", ' .
                $jsSetupObject .
                '.toggle.bind(' .
                $jsSetupObject .
                '));
                    varienGlobalEvents.attachEventHandler(
                        "formSubmit",
                        editorFormValidationHandler
                    );
                //]]>
                });



                </script>';
        return $jsString;
    }

    /**
     * @inheritdoc
     */
    public function getHtmlId()
    {
        $suffix = $this->getConfig('dynamic_id') ? '${ $.wysiwygUniqueSuffix }' : '';
        return parent::getHtmlId() . $suffix;
    }
}
