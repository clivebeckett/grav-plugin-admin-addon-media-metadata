# v.1.2.0
## 11-01-2023

1. [](#new)
    * Added Support for files in flex-object data storage

# v.1.1.0
## 05-06-2020

1. [](#new)
    * Page specific media metadata fields can be added to a page’s frontmatter (see README --> Configuration)
2. [](#improved)
    * meta.yaml file will not be created upon upload anymore: this allows the creation of the file with the setting media.auto_metadata_exif turned on (in Admin Plugin 1.9); in Admin Plugin 1.10 it did not work anyway – **NOTE: in Admin Plugin 1.10 with setting media.auto_metadata_exif turned on, make sure to save the page with a newly uploaded image before adding other metadata using this plugin**

# v1.0.2
##  16-04-2020

1. [](#improved)
    * The button next to a media file will now also be translated. It was hard-coded to English before.

# v1.0.1
##  10-04-2020

1. [](#bugfix)
    * meta.yaml file will now be created for an already uploaded media file that did not have an associated meta.yaml file before

# v1.0.0
##  20-03-2020

1. [](#improved)
    * using core technology (Grav\Common\Yaml) for parsing and writing the meta.yaml files – kudos to https://github.com/renards for adding crucial parts to the code

# v0.9.1
##  16-03-2020

1. [](#bugfix)
    * adding /vendor/ and composer.lock, needed for composer autoload functionality

# v0.9.0
##  15-03-2020

1. [](#new)
    * Initial release