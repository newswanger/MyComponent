<?php
/**
 * CMP class file for [[+packageName]] extra
 *
 * Copyright [[+copyright]] by [[+author]] [[+email]]
 * Created on [[+createdon]]
 *
[[+license]]
 *
 * @package [[+packageNameLower]]
 */


 class [[+packageName]] {
    /** @var $modx modX */
    public $modx;
    /** @var $props array */
    public $config;

    function __construct(modX &$modx,array $config = array()) {
        $this->modx =& $modx;
        $corePath = $modx->getOption('[[+packageNameLower]].core_path',null,
            $modx->getOption('core_path').'components/[[+packageNameLower]]/');
        $assetsUrl = $modx->getOption('[[+packageNameLower]].assets_url',null,
            $modx->getOption('assets_url').'components/[[+packageNameLower]]/');

        $this->config = array_merge(array(
            'corePath' => $corePath,
            'chunksPath' => $corePath.'elements/chunks/',
            'modelPath' => $corePath.'model/',
            'processorsPath' => $corePath.'processors/',

            'assetsUrl' => $assetsUrl,
            'connectorUrl' => $assetsUrl.'connector.php',
            'cssUrl' => $assetsUrl.'css/',
            'jsUrl' => $assetsUrl.'js/',
        ),$config);

        $this->modx->addPackage('[[+packageNameLower]]',$this->config['modelPath']);
        if ($this->modx->lexicon) {
            $this->modx->lexicon->load('[[+packageNameLower]]:default');
        }
    }

    /**
     * Initializes [[+packageName]] based on a specific context.
     *
     * @access public
     * @param string $ctx The context to initialize in.
     * @return string The processed content.
     */
    public function initialize($ctx = 'mgr') {
        $output = '';
        switch ($ctx) {
            case 'mgr':
                if (!$this->modx->loadClass('[[+packageNameLower]].request.[[+packageName]]ControllerRequest',
                    $this->config['modelPath'],true,true)) {
                        return 'Could not load controller request handler.';
                }
                $this->request = new [[+packageName]]ControllerRequest($this);
                $output = $this->request->handleRequest();
                break;
        }
        return $output;
    }
}