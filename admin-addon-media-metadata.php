<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Page\Media;
use Grav\Common\Plugin;
use Grav\Common\Yaml;
use RocketTheme\Toolbox\File\File;

/**
 * Class AdminAddonMediaMetadataPlugin
 * some of the code is based on the Admin Addon Media Rename by Dávid Szabó
 *     see https://github.com/david-szabo97/grav-plugin-admin-addon-media-rename
 * @package Grav\Plugin
 */
class AdminAddonMediaMetadataPlugin extends Plugin
{
    const ROUTE = '/admin-addon-media-metadata';
    const TASK_METADATA = 'AdminAddonMediaMetadataEdit';

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *	 that the plugin wants to listen to. The key of each
     *	 array section is the event that the plugin listens to
     *	 and the value (in the form of an array) contains the
     *	 callable (or function) as well as the priority. The
     *	 higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            ['autoload', 100000], // TODO: Remove when plugin requires Grav >=1.7
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Composer autoload.
     * is
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    public function getPath()
    {
        return '/' . trim($this->grav['admin']->base, '/') . '/' . trim(self::ROUTE, '/');
    }

    public function buildBaseUrl()
    {
        return rtrim($this->grav['uri']->rootUrl(true), '/') . '/' . trim($this->getPath(), '/');
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        if (!$this->isAdmin() || !$this->grav['user']->authenticated) {
            return;
        }

        if ($this->grav['uri']->path() == $this->getPath()) {
            $this->enable([
                'onPagesInitialized' => ['processRenameRequest', 0]
            ]);
            return;
        }

        $this->enable([
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
            'onPagesInitialized' => ['onTwigExtensions', 0],
            'onAdminAfterAddMedia' => ['createMetaYaml', 0],
            'onAdminTaskExecute' => ['editMetaDataFile', 0],
//            'onAdminTaskExecute'  => ['editTest', 0],
        ]);
    }

    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    public function onTwigExtensions()
    {
        $page = $this->grav['admin']->page(true);
        if (!$page) {
            return;
        }

        /**
         * get metadata form field config from plugin admin-addon-media-metadata.yaml file
         */
        // TODO: optionally replace by local form definition file (in the current page folder)
        $formFields = $this->config->get('plugins.admin-addon-media-metadata.metadata_form');

        /**
         * list all needed data keys from the fields configuration
         */
        $arrMetaKeys = $this->editableFields($formFields['fields']);

        $path = $page->path();
        $media = new Media($path);
        $allMedia = $media->all();
        $arrFiles = [];
        $i = 0;
        foreach ($allMedia as $filename => $file) {
            $metadata = $file->meta();
            $arrFiles[$filename] = [
                'filename' => $filename,
            ];
            /**
             * for each file: write stored metadata for each editable field into an array
             * this will be output as inline JS variable adminAddonMediaMetadata
             */
            foreach ($arrMetaKeys as $metaKey => $info) {
                $arrFiles[$filename][$metaKey] = $metadata->$metaKey;
            }
            $i++;
        }
        $inlineJs = 'var mediaListOnLoad = ' . json_encode($arrFiles) . ';';
        $modal = $this->grav['twig']->twig()->render('metadata-modal.html.twig', $formFields);
        $jsConfig = [
            'PATH' => $this->buildBaseUrl() . '/' . $page->route() . '/task:' . self::TASK_METADATA,
            'MODAL' => $modal
        ];
        $inlineJs .= PHP_EOL . 'var adminAddonMediaMetadata = ' . json_encode($jsConfig) . ';';
        $this->grav['assets']->addInlineJs($inlineJs, -1000);
        $this->grav['assets']->addCss('plugin://admin-addon-media-metadata/admin-addon-media-metadata.css', -1000);
        $this->grav['assets']->addJs('plugin://admin-addon-media-metadata/admin-addon-media-metadata.js', -1000);
    }

    public function editTest()
    {
        $this->outputError('blob');
    }

    public function editMetaDataFile($e)
    {
        $method = $e['method'];
        if ($method === 'task' . self::TASK_METADATA) {
            $fileName = $_POST['filename'];

            $pageObj = $this->grav['admin']->page();
            $basePath = $pageObj->path() . DS;

            $filePath = $basePath . $fileName;

            if (!file_exists($filePath)) {
                $this->outputError($this->grav['language']->translate(['PLUGIN_ADMIN_ADDON_MEDIA_METADATA.ERRORS.MEDIA_FILE_NOT_FOUND', $filePath]));
            } else {
                $metaDataFilePath = $filePath . '.meta.yaml';

                /**
                 * get array of all current metadata for the file
                 * this is to avoid overwriting data that has been added to the meta.yaml file in the file browser
                 */
                $storedMetaData = Yaml::parse(file_get_contents($metaDataFilePath));

                /**
                 * get the list of form data from the fields configuration
                 */
                $arrMetaKeys = $this->editableFields();

                /**
                 * overwrite the currently stored data for each field in the form
                 */
                foreach ($arrMetaKeys as $metaKey => $info) {
                    if (isset($_POST[$metaKey])) {
                        // multiline entries for textareas of single line for text fields
                        if ($info['type'] === 'textarea') {
                            $textarea = explode(PHP_EOL, $_POST[$metaKey]);
                            if (count($textarea) > 1) {
                                $i = 0;
                                $multiline = '';
                                foreach ($textarea as $singleLine) {
                                    $multiline .= ($i === 0) ? '|' : '';
                                    $multiline .= PHP_EOL . '  ' . $singleLine;
                                    $i++;
                                }
                            } else {
                                $multiline = $_POST[$metaKey];
                            }
                            $storedMetaData[$metaKey] = $multiline;
                        } else {
                            $storedMetaData[$metaKey] = $_POST[$metaKey];
                        }
                    }
                }

                /**
                 * Get an instance of the meta file and write the data to it
                 * @see \Grav\Common\Page\Media
                 */
                $metaDataFile = File::instance($metaDataFilePath);
                $metaDataFile->save(Yaml::dump($storedMetaData));

                //$this->outputError($newYamlText);
            }
        }
    }

    /**
     * creates an image.meta.yaml file
     * this file will be deleted by the core when deleting an image
     * will be called after a media file has been added (see onPluginsInitialized())
     */
    public function createMetaYaml()
    {
        $fileName = $_FILES['file']['name'];

        $pageObj = $this->grav['admin']->page();
        $basePath = $pageObj->path() . DS;

        $filePath = $basePath . $fileName;
        if (!file_exists($filePath)) {
            $this->outputError($this->grav['language']->translate(['PLUGIN_ADMIN_ADDON_MEDIA_METADATA.ERRORS.MEDIA_FILE_NOT_FOUND', $filePath]));
        } else {
            // TODO: do that only for image files?
            $metaDataFileName = $fileName . '.meta.yaml';
            $metaDataFilePath = $basePath . $metaDataFileName;
            if (!file_exists($metaDataFilePath)) {
                /**
                 * get the list of form data from the fields configuration
                 */
                $arrMetaKeys = $this->editableFields();

				$newMetaData = [];
                foreach ($arrMetaKeys as $metaKey => $info) {
	                $newMetaData[$metaKey] = '';
	            }

                /**
                 * Get an instance of the meta file and write the data to it
                 * @see \Grav\Common\Page\Media
                 */
                $metaDataFile = File::instance($metaDataFilePath);
                $metaDataFile->save(Yaml::dump($newMetaData));
            }
        }
    }

    /**
     * return all editable fields from form configuration
     */
    private function editableFields($fieldsConf = null)
    {
        if ($fieldsConf === null) {
            /**
             * get metadata form field config from plugin admin-addon-media-metadata.yaml file
             */
            // TODO: optionally replace by local form definition file (in the current page folder)
            $formFields = $this->config->get('plugins.admin-addon-media-metadata.metadata_form');
            $fieldsConf = $formFields['fields'];
        }
        $arrMetaKeys = [];
        foreach ($fieldsConf as $singleFieldConf) {
            if (isset($singleFieldConf['name'], $singleFieldConf['type']) && $singleFieldConf['name'] !== 'filename') {
                $arrMetaKeys[$singleFieldConf['name']] = [
                    'name' => $singleFieldConf['name'],
                    'type' => $singleFieldConf['type']
                ];
            }
        }
        return $arrMetaKeys;
    }

    public function outputError($msg)
    {
        header('HTTP/1.1 400 Bad Request');
        die(json_encode(['error' => ['msg' => $msg]]));
    }
}
