<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Bob Ray
 * Date: 8/16/12
 * Time: 11:29 PM
 * To change this template use File | Settings | File Templates.
 */


/**
 * Test class for Bootstrap.
 * Generated by PHPUnit on 2012-03-02 at 23:02:19.
 * @outputBuffering disabled
 */
class BootStrapTest extends PHPUnit_Framework_TestCase
{
    /* @var $bootstrap Bootstrap */
    public $bootstrap;
    /* @var $modx modX */
    public $modx;
    /* @var $categoryName string */
    public $categoryName;
    /* @var $utHelpers UtHelpers */
    public $utHelpers;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before each test is executed.
     */
    protected function setUp()
    {
        // echo "\n---------------- SETUP --------------------";
        require_once dirname(__FILE__) . '/build.config.php';
        require_once dirname(__FILE__) . '/uthelpers.class.php';
        require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
        $this->utHelpers = new UtHelpers();
        $modx = new modX();
        $modx->initialize('mgr');
        $modx->setLogLevel(modX::LOG_LEVEL_ERROR);
        $modx->setLogTarget('ECHO');

        require_once MODX_ASSETS_PATH . 'mycomponents/mycomponent/_build/utilities/bootstrap.class.php';

        $this->bootstrap = new Bootstrap($modx);
        $this->modx =& $this->bootstrap->modx;
        /* @var $categoryObj modCategory */
        $this->bootstrap->init(dirname(__FILE__) . '/build.config.php');
        if ($this->bootstrap->props['category'] != 'UnitTest') {
            die('wrong config');
        }
        $this->utHelpers->rrmdir($this->bootstrap->targetBase);
        $this->utHelpers->removeElements($this->modx, $this->bootstrap);
        $this->utHelpers->removeResources($this->modx, $this->bootstrap);
        $this->utHelpers->removePropertySets($this->modx, $this->bootstrap);
        $this->bootstrap->createCategory();
    }


    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after each test is executed.
     */
    protected function tearDown()
    {
        // echo "\n---------------- TEARDOWN --------------------";
        /* @var $category modCategory */
        $this->utHelpers->rrmdir($this->bootstrap->targetBase);
        $category = $this->modx->getObject('modCategory', array('category' => 'UnitTest'));
        if ($category) $category->remove();
        $this->utHelpers->rrmdir($this->bootstrap->targetBase);
        $this->utHelpers->removeElements($this->modx, $this->bootstrap);
        $this->utHelpers->removeResources($this->modx, $this->bootstrap);
        $this->utHelpers->removePropertySets($this->modx, $this->bootstrap);
        $this->bootstrap = null;

    }

    /**
     * @covers Bootstrap::init
     */
    public function testinit()
    {
        /* make sure $modx and $props are set */
        $this->assertTrue(isset($this->bootstrap->modx));
        $this->assertInstanceOf('modX', $this->bootstrap->modx);
        $this->assertTrue(isset($this->bootstrap->props));
        $this->assertTrue(is_array($this->bootstrap->props));

        /* make sure basic member variables are not empty */
        $this->assertNotEmpty($this->bootstrap->packageName);
        $this->assertNotEmpty($this->bootstrap->packageNameLower);
        $this->assertNotEmpty($this->bootstrap->targetBase);
        $this->assertNotEmpty($this->bootstrap->targetAssets);
        $this->assertNotEmpty($this->bootstrap->targetCore);
        $this->assertNotEmpty($this->bootstrap->dirPermission);
        $this->assertNotEmpty($this->bootstrap->filePermission);
        $this->assertTrue(is_array($this->bootstrap->makeStatic));
        /* make sure helpers class was loaded  */
        $this->assertTrue(method_exists($this->bootstrap->helpers,'replaceTags'));
    }

    public function testCategory() {
        /* @var $category modCategory */
        $category = $this->modx->getObject('modCategory', $this->bootstrap->categoryId);

        $this->assertInstanceOf('modCategory', $category);
        $this->assertEquals($this->bootstrap->props['category'],$category->get('category'));
    }
    public function testCreateElements() {
        /* @var $obj modElement */
        $elements = $this->bootstrap->props['elements'];
        $this->assertNotEmpty($elements);

        /* should not create elements */
        $this->bootstrap->props['createElementObjects'] = false;
        $this->bootstrap->props['createElementFiles'] = false;
        $this->bootstrap->createElements();
        $elements = $this->bootstrap->props['elements'];
        $this->assertNotEmpty($elements);
        foreach ($elements as $elementType => $elementNames) {
            if (!empty($elementNames)) {
                $alias = $this->bootstrap->helpers->getNameAlias($elementType);
                $elementNames = explode(',', $elementNames);
                    foreach ($elementNames as $elementName) {
                        $obj = $this->modx->getObject($elementType, array($alias => $elementName));
                        $this->assertNotInstanceOf($elementType,$obj);
                        $fileName = $this->bootstrap->helpers->getFileName($elementName, $elementType);
                        $codeDir = $this->bootstrap->helpers->getCodeDir($this->bootstrap->targetCore, $elementType);
                        $this->assertFalse(file_exists($codeDir . '/' . $fileName));
                    }
                }
        }
        /* create files and objects */
        $this->bootstrap->props['createElementObjects'] = true;
        $this->bootstrap->props['createElementFiles'] = true;
        $this->bootstrap->createElements();
        $elements = $this->bootstrap->props['elements'];
        $this->assertNotEmpty($elements);
        foreach ($elements as $elementType => $elementNames) {
            if (!empty($elementNames)) {
                $alias = $this->bootstrap->helpers->getNameAlias($elementType);
                $elementNames = explode(',', $elementNames);
                foreach ($elementNames as $elementName) {
                    $obj = $this->modx->getObject($elementType, array($alias => $elementName));
                    $this->assertInstanceOf($elementType, $obj);
                    $fileName = $this->bootstrap->helpers->getFileName($elementName, $elementType);
                    $codeDir = $this->bootstrap->helpers->getCodeDir($this->bootstrap->targetCore, $elementType);

                    if ($elementType != 'modTemplateVar') {
                        $this->assertTrue(file_exists($codeDir . '/' . $fileName), $fileName . '/' . $fileName);
                    }
                }
            }
        }
    }
    public function testCreateResources() {
        /* @var $r modResource */
        $this->bootstrap->createResources();
        $resources = $this->bootstrap->props['resources'];
        $resources = explode(',', $resources);
        foreach ($resources as $resource) {
            $r = $this->modx->getObject('modResource', array('pagetitle' => $resource));
            $this->assertInstanceOf('modResource', $r);
        }
    }
    public function testCreateBasics() {
        $defaults = $this->bootstrap->props['defaultStuff'];
        $this->bootstrap->props['defaultStuff']['utilities'] = true;
        $this->bootstrap->createBasics();
        $this->assertTrue(file_exists($this->bootstrap->targetBase . '_build/build.transport.php'));
        $this->assertTrue(file_exists($this->bootstrap->targetBase . '_build/build.config.php'));
        $this->assertTrue(is_dir($this->bootstrap->targetBase. '_build/utilities'));
        $this->assertFalse(file_exists($this->bootstrap->targetBase . '_build/utilities/config/' . $this->bootstrap->packageNameLower . 'config.php'));
        $this->utHelpers->rrmdir($this->bootstrap->targetBase);
        $this->assertFalse(is_dir($this->bootstrap->targetBase . '_build'));

        $this->bootstrap->props['defaultStuff']['utilities'] = false;
        $this->bootstrap->createBasics();
        $this->assertTrue(file_exists($this->bootstrap->targetBase . '_build/build.transport.php'));
        $this->assertNotEmpty(file_get_contents($this->bootstrap->targetBase . '_build/build.transport.php'));
        $this->assertTrue(file_exists($this->bootstrap->targetBase . '_build/build.config.php'));
        $this->assertNotEmpty(file_get_contents($this->bootstrap->targetBase . '_build/build.config.php'));

        $this->assertFalse(is_dir($this->bootstrap->targetBase . '_build/utilities'));

        $this->assertNotEmpty($defaults['docs']);
        $docs = explode(',', $defaults['docs']);
        foreach($docs as $doc) {
            $this->assertTrue(file_exists($this->bootstrap->targetCore . 'docs/' . $doc));
            $this->assertNotEmpty(file_get_contents($this->bootstrap->targetCore . 'docs/' . $doc));
        }
        $this->assertTrue(file_exists($this->bootstrap->targetBase . 'readme.md'));
        $this->assertNotEmpty(file_get_contents($this->bootstrap->targetBase . 'readme.md'));

        $this->assertNotEmpty($this->bootstrap->props['languages']);
        $languages = $this->bootstrap->props['languages'];
        foreach ($languages as $language => $files) {
            $fileNames = explode(',', $files);
            foreach ($fileNames as $fileName){
                $this->assertFileExists($this->bootstrap->targetCore . 'lexicon/' . $language . '/' . $fileName . '.inc.php', 'LANGUAGE: ' . $language . '  FILE: ' . $fileName . '.inc.php');
                $this->assertNotEmpty(file_get_contents($this->bootstrap->targetCore . 'lexicon/' . $language . '/' . $fileName . '.inc.php', 'LANGUAGE: ' . $language . '  FILE: ' . $fileName . '.inc.php'));
            }
        }
    }
    public function testCreateAssetsDirs() {
        $this->bootstrap->createAssetsDirs();
        $this->assertFileExists($this->bootstrap->targetAssets . 'css/' . $this->bootstrap->packageNameLower . '.css');
        $this->assertnotEmpty(file_get_contents($this->bootstrap->targetAssets . 'css/' . $this->bootstrap->packageNameLower . '.css'));
        $this->assertFileExists($this->bootstrap->targetAssets . 'js/' . $this->bootstrap->packageNameLower . '.js');
        $this->assertnotEmpty(file_get_contents($this->bootstrap->targetAssets . 'js/' . $this->bootstrap->packageNameLower . '.js'));
    }

    public function testConnectSystemEventsToPlugins() {
        /* check connections */
        $this->bootstrap->createElements();
        $this->bootstrap->connectSystemEventsToPlugins();
        $plugins = $this->bootstrap->props['elements']['modPlugin'];
        $this->assertNotEmpty($plugins);
        $plugins = explode(',',$plugins);
        foreach($plugins as $plugin) {
            /* @var $pluginObj modPlugin */
            /* @var $pluginEvent modPluginEvent */
            $alias = $this->bootstrap->helpers->getNameAlias('modPlugin');
            $pluginObj = $this->modx->getObject('modPlugin', array ($alias => $plugin));
            $this->assertInstanceOf('modPlugin', $pluginObj);
            $events = $this->bootstrap->props['pluginEvents'][$plugin];
            $this->assertNotEmpty($events);
            $events = explode(',', $events);
            foreach ($events as $event) {
                $fields = array (
                    'pluginid' => $pluginObj->get('id'),
                    'event' => $event,
                );
                $eventName = $this->modx->getObject('modEvent', array('name' => $event));
                $this->assertInstanceOf('modEvent', $eventName);
                if (strstr($this->bootstrap->props['newSystemEvents'],$event)) {
                    /* new event */
                    $this->assertEquals($eventName->get('groupname'), $this->bootstrap->props['category']);
                }
                $pluginEvent = $this->modx->getObject('modPluginEvent', $fields);
                $this->assertInstanceOf('modPluginEvent', $pluginEvent);
            }
        }

        /* check creation of plugin resolver */
        $this->assertFileExists($this->bootstrap->targetBase . '_build/resolvers/plugin.resolver.php');
        $this->assertNotEmpty(file_get_contents($this->bootstrap->targetBase . '_build/resolvers/plugin.resolver.php'));

        /* remove event names from system_eventnames */
        /* @var $eventName modEvent */
        $names = $this->bootstrap->props['newSystemEvents'];
        $names = empty($names) ? array() : explode(',', $names);
        foreach ($names as $name) {
            $eventName = $this->modx->getObject('modEvent', array('name' => $name));
            if ($eventName) {
                $eventName->remove();
            }
        }
    }

    public function testConnectTvsToTemplates()
    {
        $this->bootstrap->connectTvsToTemplates();
        $this->assertFileExists($this->bootstrap->targetBase . '_build/resolvers/tv.resolver.php');
        $this->assertNotEmpty(file_get_contents($this->bootstrap->targetBase . '_build/resolvers/tv.resolver.php'));
    }

    public function testCreateValidators()
    {
        $this->bootstrap->createValidators();
        $validators = explode(',', $this->bootstrap->props['validators']);

        foreach ($validators as $validator) {
            $validator = $validator == 'default'? $this->bootstrap->packageNameLower : $validator;
            $this->assertFileExists($this->bootstrap->targetBase . '_build/validators/' . $validator . '.validator.php');
            $this->assertNotEmpty(file_get_contents($this->bootstrap->targetBase . '_build/validators/' . $validator . '.validator.php'));

        }
    }

    public function testCreateExtraResolvers()
    {
        $this->bootstrap->createExtraResolvers();
        $resolvers = explode(',', $this->bootstrap->props['resolvers']);

        foreach ($resolvers as $resolver) {
            $resolver = $resolver == 'default' ? $this->bootstrap->packageNameLower : $resolver;
            $this->assertFileExists($this->bootstrap->targetBase . '_build/resolvers/' . $resolver . '.resolver.php');
            $this->assertNotEmpty(file_get_contents($this->bootstrap->targetBase . '_build/resolvers/' . $resolver . '.resolver.php'));

        }
    }
    public function testCreateInstallOption () {
        $this->bootstrap->createInstallOptions();
        $this->assertFileExists($this->bootstrap->targetBase . '_build/install.options/user.input.php');
        $this->assertNotEmpty(file_get_contents($this->bootstrap->targetBase . '_build/install.options/user.input.php'));
    }

    public function testCreatePropertySets() {
        /* @var $setObj modPropertySet */
        $this->bootstrap->createPropertySets();
        $sets = $this->bootstrap->props['propertySets'];
        $sets = explode(',', $sets);
        foreach ($sets as $set) {
            $alias = $this->bootstrap->helpers->getNameAlias('modPropertySet');
            $setObj = $this->modx->getObject('modPropertySet', array($alias => $set));
            $this->assertInstanceOf('modPropertySet', $setObj);
            $this->assertEquals($setObj->get('name'), $set);
            $this->assertEquals($setObj->get('category'), $this->bootstrap->categoryId);
        }

    }
    public function testConnectPropertySetsToElements() {
        $this->bootstrap->createPropertySets();
        $this->bootstrap->createElements();
        $this->bootstrap->connectPropertySetsToElements();
        $propertySets = $this->bootstrap->props['propertySetElements'];
        $this->assertNotEmpty($propertySets);
        $this->modx->log(MODX::LOG_LEVEL_INFO, '    Connecting ' . count($propertySets) . ' Property Sets to Elements');

        foreach ($propertySets as $propertySetName => $elements) {
            $alias = $this->bootstrap->helpers->getNameAlias('modPropertySet');
            $propertySetObj = $this->modx->getObject('modPropertySet', array($alias => $propertySetName));
            $this->assertInstanceOf('modPropertySet', $propertySetObj);

            $els = empty($elements) ? array() : explode(',', $elements);
            foreach ($els as $el) {
                $data = explode(':', $el);
                $elementName = trim($data[0]);
                $elementType = trim($data[1]);
                $alias = $this->bootstrap->helpers->getNameAlias($elementType);
                $elementObj = $this->modx->getObject($elementType, array($alias => $elementName));
                $this->assertInstanceOf($elementType,$elementObj);

                /* @var $elementObj modElement */
                /* @var $propertySetObj modPropertySet */
                /* @var $elementPropertySet modElementPropertySet */
                $elementId = $elementObj->get('id');
                $propertySetId = $propertySetObj->get('id');
                $elementPropertySet = $this->modx->getObject('modElementPropertySet', array(
                    'element' => $elementId,
                    'property_set' => $propertySetId,
                ));
                $this->assertInstanceOf('modElementPropertySet',$elementPropertySet);
            }

        }
    }

    public function testCreateClassFiles() {
        $this->utHelpers->rrmdir($this->bootstrap->targetCore . '/model');
        $this->bootstrap->createClassFiles();
        $classes = $this->bootstrap->props['classes'];
        $this->assertNotEmpty($classes);
        $baseDir = $this->bootstrap->targetCore . 'model';
        foreach ($classes as $className => $data) {
            $data = explode(':', $data);
            if (!empty($data[1])) {
                $dir = $baseDir . '/' . $data[0] . '/' . $data[1];
            } else { /* no directory */
                $dir = $baseDir . '/' . $data[0];
            }
            $fileName = strtolower($className) . '.class.php';
            $this->assertTrue(is_dir($dir));
            $this->assertFileExists($dir . '/' . $fileName);
            $this->assertNotEmpty(file_get_contents($dir . '/' . $fileName));
            $content = file_get_contents($dir . '/' . $fileName);
            /* check for constructor */
            $this->assertNotEmpty(strstr($content, '__construct'));
            /* check class name */
            $this->assertNotEmpty(strstr($content, 'class ' . $className));
            /* check for license */
            $this->assertNotEmpty(strstr($content, 'License'));
            /* make sure all placeholders got replaced */
            $this->assertEmpty(strstr($content, '[[+'));
        }

    }
    public function testHelpers() {
        $dir = $this->bootstrap->targetCore . '/dummy/dummy';
        $fileName = 'dummy.php';
        $this->assertFileNotExists($dir .'/' . $fileName);
        $this->bootstrap->helpers->writeFile($dir, $fileName, 'Something');
        $this->assertFileExists($dir . '/' . $fileName);
        $this->assertEquals(file_get_contents($dir . '/' . $fileName), 'Something');
        $this->utHelpers->rrmdir($this->bootstrap->targetBase);
        $this->assertFileNotExists($dir . '/' . $fileName);

        /* test removeElements */
        $this->utHelpers->removeElements($this->modx, $this->bootstrap);
        $this->bootstrap->createElements();
        $this->utHelpers->removeElements($this->modx, $this->bootstrap);
        $elements = $this->bootstrap->props['elements'];
        foreach ($elements as $elementType => $elementNames) {
            $alias = $this->bootstrap->helpers->getNameAlias($elementType);
            $elementNames = empty($elementNames)? array() : explode(',', $elementNames);
            foreach($elementNames as $elementName) {
                $obj = $this->modx->getObject($elementType, array($alias => $elementName));
                $this->assertNull($obj);
            }
        }
        /* test removeResources */
        $this->bootstrap->createResources();
        $this->utHelpers->removeResources($this->modx, $this->bootstrap);
        $resources = $this->bootstrap->props['resources'];
        $resources = explode(',', $resources);
        foreach ($resources as $resource) {
            $r = $this->modx->getObject('modResource', array('pagetitle' => $resource));
            $this->assertNull($r);
        }
        /* test removePropertySets */
        $this->bootstrap->createPropertySets();
        $this->utHelpers->removePropertySets($this->modx, $this->bootstrap);
        $sets = $this->bootstrap->props['propertySets'];
        $this->assertNotEmpty($sets);
        $sets = empty ($sets)? array() : explode(',', $sets);
        foreach ($sets as $set) {
            $alias = $this->bootstrap->helpers->getNameAlias('modPropertySet');
            $setObj = $this->modx->getObject('modPropertySet', array($alias => $set));
            $this->assertNull($setObj);
        }
    }
}