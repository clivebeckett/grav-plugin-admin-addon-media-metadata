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
     * that the plugin wants to listen to. The key of each
     * array section is the event that the plugin listens to
     * and the value (in the form of an array) contains the
     * callable (or function) as well as the priority. The
     * higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            ['autoload', 100000], // TODO: Remove when plugin requires Grav >=1.7
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
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
            'onTwigInitialized' => ['onTwigInitialized', 0],
            'onPagesInitialized' => ['onTwigExtensions', 0],
            //'onAdminAfterAddMedia' => ['createMetaYaml', 0], // removing the call of this method: see method below
            'onAdminTaskExecute' => ['editMetaDataFile', 0],
        ]);
    }

    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    public function onTwigInitialized()
    {
        $this->grav['twig']->twig()->addFunction(
            new \Twig_SimpleFunction('aammPath', [$this, 'getCurrentObjectPath'])
        );
    }

    public function onTwigExtensions()
    {
        $contentObject = null;
        $page = $this->grav['admin']->page(true);
        $isFlex = $this->grav['config']->get( 'plugins.flex-objects.enabled' );

        // is flex-objects plugin enabled
        // we assume pages will be managed by flex-object
        if ($isFlex)
        {
            $contentObject = $this->getFlexPage();
        }
        // if the flex-objects plugin is not active, we use the default page model
        if(!$contentObject)
        {
            $contentObject = $page;
        }

        /**
         * get metadata form field config from plugin admin-addon-media-metadata.yaml file
         * or local override in user/config/plugins/admin-addon-media-metadata.yaml
         * or from page frontmatter
         */
        //$formFields = $this->config->get('plugins.admin-addon-media-metadata.metadata_form');
        $config = $this->mergeConfig($page, true);
        $formFields = $config->get('metadata_form');

        // clean up legacy configs
        foreach ( $formFields['fields'] as $key => $field )
        {
            if ( array_key_exists('name', $field) && in_array( $field['name'], [ 'filename', 'filepath' ] ) )
            {
                unset( $formFields['fields'][$key] );
            }
        }

        // required hidden fields
        $base_fields = $config->get( 'base_fields' );
        $formFields['fields'] = array_merge( $base_fields, $formFields['fields'] );

        /**
         * list all needed data keys from the fields configuration
         */
        $arrMetaKeys = $this->editableFields($formFields['fields']);

        // get all media files from the content object
        $allMedia = $contentObject->getMedia();
        $mediaSource = $allMedia->getPath();
        $arrFiles = [];

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
            // add the files folder path to the array
            $arrFiles[$filename]['filepath'] = realpath( $mediaSource ) ;
        }

        $jsArrFormFields = '';
        $i = 0;
        foreach ($arrMetaKeys as $metaKey => $info) {
            $jsArrFormFields .= ($i > 0) ? ",'" . $metaKey . "'" : "'" . $metaKey . "'";
            $i++;
        }

        $inlineJs = 'var metadataFormFields = [' . $jsArrFormFields . '];';
        $inlineJs .= PHP_EOL . 'var mediaListOnLoad = ' . json_encode($arrFiles) . ';';
        $modal = $this->grav['twig']->twig()->render('metadata-modal.html.twig', $formFields);
        $jsConfig = [
            'PATH' => $this->getAdminBase() . '/update.json/task:' . self::TASK_METADATA,
            'MODAL' => $modal
        ];
        $inlineJs .= PHP_EOL . 'var adminAddonMediaMetadata = ' . json_encode($jsConfig) . ';';
        $inlineJs .= PHP_EOL . $this->grav['twig']->twig()->render('additionalInlineJS.html.twig');
        $this->grav['assets']->addInlineJs($inlineJs, -1000);
        $this->grav['assets']->addCss('plugin://admin-addon-media-metadata/admin-addon-media-metadata.css', -1000);
        $this->grav['assets']->addJs('plugin://admin-addon-media-metadata/admin-addon-media-metadata.js', -1000);
    }

    public function editTest()
    {
        $this->outputError('blob');
    }

    /**
     * writes metadata into the [mediafile].meta.yaml file
     * creates the .meta.yaml file if it does not yet exist
     */
    public function editMetaDataFile($e)
    {
        $method = $e['method'];
        if ($method === 'task' . self::TASK_METADATA) {

            if (isset($_POST['filepath']) && empty($_POST['filepath']))
            {
                // without a filepath, we cannot save.
                $this->outputError($this->grav['language']->translate(['PLUGIN_ADMIN_ADDON_MEDIA_METADATA.ERRORS.NO_FILEPATH']));
                return;
            }

            $basePath = $_POST['filepath'];
            $fileName = $_POST['filename'];

            $filePath = $basePath . '/' . $fileName;

/**
 * temporarily removing the condition that checks for the media file to exist
 * as in Admin plugin 1.10 a media file will be uploaded to a tmp folder first
 * and moved to the page folder on saving the page
 *
 * there needs to be a better solution for this
 *
 * also: take care of system.yaml → media.auto_metadata_exif
 *     if set to TRUE, a meta.yaml with EXIF data will be written, but only if the meta.yaml does not yet exist
 *     in Admin 1.10 you need to save a page in order to have the meta.yaml file with EXIF data created
 *     in Admin 1.9 this is not a problem since this plugin now (>=1.1.0) writes metadata only when needed
 */
//          if (!file_exists($filePath)) {
//              $this->outputError($this->grav['language']->translate(['PLUGIN_ADMIN_ADDON_MEDIA_METADATA.ERRORS.MEDIA_FILE_NOT_FOUND', $filePath]));
//          } else {
                $metaDataFilePath = $filePath . '.meta.yaml';

                /**
                 * get the list of form data from the fields configuration
                 */
                $arrMetaKeys = $this->editableFields();

                if (file_exists($metaDataFilePath)) {
                    /**
                     * get array of all current metadata for the file
                     * this is to avoid overwriting data that has been added to the meta.yaml file in the file browser
                     */
                    $storedMetaData = Yaml::parse(file_get_contents($metaDataFilePath));

                    /**
                     * overwrite the currently stored data for each field in the form
                     */
                    foreach ($arrMetaKeys as $metaKey => $info) {
                        if (isset($_POST[$metaKey])) {
                            $storedMetaData[$metaKey] = $_POST[$metaKey];
                        }
                    }

                } else {
                    /**
                     * create metaData in case the meta data file does not yet exist
                     */
                    $storedMetaData = [];
                    foreach ($arrMetaKeys as $metaKey => $info) {
                        if (isset($_POST[$metaKey])) {
                            $storedMetaData[$metaKey] = $_POST[$metaKey];
                        } else {
                            $storedMetaData[$metaKey] = '';
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

                // return something meaningful
                $this->grav['admin']->json_response = [
                    'status'  => 'success',
                    'media-metadata' => 'true',
                    'file' => $metaDataFilePath,
                ];
//          }
        }
    }

/**
 * this method will not be called in Admin Plugin v1.10 (up until rc.11 anyway)
 * additionally, writing the YAML file on upload will meddle with the system functionality
 *     that writes exif data
 *     in Admin Plugin v1.9 this functionality writes the meta.yaml file
 *         on upload, this plugin will then just add the other metadata
 *         when writing the meta.yaml file upon upload the system functionality will be overwritten
 *     in Admin Plugin v1.10 you need to save the page before using this plugin on a file
 *         if you want the exif data to be stored
 */
    /**
     * creates an image.meta.yaml file
     * this file will be deleted by the core when deleting an image
     * will be called after a media file has been added (see onPluginsInitialized())
     */
//    public function createMetaYaml()
//    {
//        $fileName = $_FILES['file']['name'];
//
//        $pageObj = $this->grav['admin']->page();
//        $basePath = $pageObj->path() . DS;
//
//        $filePath = $basePath . $fileName;
//        if (!file_exists($filePath)) {
//            $this->outputError($this->grav['language']->translate(['PLUGIN_ADMIN_ADDON_MEDIA_METADATA.ERRORS.MEDIA_FILE_NOT_FOUND', $filePath]));
//        } else {
//            // TODO: do that only for image files?
//            $metaDataFileName = $fileName . '.meta.yaml';
//            $metaDataFilePath = $basePath . $metaDataFileName;
//            if (!file_exists($metaDataFilePath)) {
//                /**
//                 * get the list of form data from the fields configuration
//                 */
//                $arrMetaKeys = $this->editableFields();
//
//                $newMetaData = [];
//                foreach ($arrMetaKeys as $metaKey => $info) {
//                    $newMetaData[$metaKey] = '';
//                }
//
//                /**
//                 * Get an instance of the meta file and write the data to it
//                 * @see \Grav\Common\Page\Media
//                 */
//                $metaDataFile = File::instance($metaDataFilePath);
//                $metaDataFile->save(Yaml::dump($newMetaData));
//            }
//        }
//    }

    /**
     * return all editable fields from form configuration
     */
    private function editableFields($fieldsConf = null)
    {
        if ($fieldsConf === null) {
            $page = $this->grav['admin']->page(true);
            if (!$page) {
                return;
            }
            /**
             * get metadata form field config from plugin admin-addon-media-metadata.yaml file
             * or local override in user/config/plugins/admin-addon-media-metadata.yaml
             * or from page frontmatter
             */
            //$formFields = $this->config->get('plugins.admin-addon-media-metadata.metadata_form');
            $config = $this->mergeConfig($page, true);
            $formFields = $config->get('metadata_form');
            $fieldsConf = $formFields['fields'];
        }
        $arrMetaKeys = [];
        foreach ($fieldsConf as $singleFieldConf) {
            if (
                isset($singleFieldConf['name'], $singleFieldConf['type']) &&
                $singleFieldConf['type'] !== 'hidden'
            ) {
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

    public function getPath()
    {
        return '/' . trim($this->grav['admin']->base, '/') . '/' . trim(self::ROUTE, '/');
    }

    public function getAdminBase()
    {
        return rtrim($this->grav['uri']->rootUrl(true), '/') . '/' . trim($this->grav['admin']->base, '/');
    }

    public function getFlexPage()
    {
        $page = $this->grav['page'];
        $header = get_object_vars( $page->header() );
        // can we determine this is a flex object?
        if (!isset($header['controller']))
        {
            return;
        }

        // get the details of that object
        $flex = $this->grav['flex'];
        $target = $header['controller'];
        // is this an existing flex directory?
        if ( $flex->getDirectory( $target['type'] ) )
        {
            $object = $flex->getObject( $target['key'], $target['type'] );
            return $object;
        }
        else
        {
            return null;
        }
    }

    public function getCurrentObjectPath()
    {
        $contentObject = null;
        $page = $this->grav['admin']->page(true);
        $isFlex = $this->grav['config']->get( 'plugins.flex-objects.enabled' );

        if ($isFlex)
        {
            $contentObject = $this->getFlexPage();
        }
        // if the flex-objects plugin is not active, we use the default page model
        if(!$contentObject)
        {
            $contentObject = $page;
        }
        return GRAV_ROOT . '/' . $contentObject->getMedia()->getPath();
    }
}
