# 1.2.4
## 2024-06-03

1. [](#bugfix)
    * Fix crash when custom metadata fields have no name defined (#32)
2. [](#improved)
    * Changelog format streamlined

# 1.2.3
## 2024-03-11

1. [](#bugfix)
    * making sure all mandatory hidden fields are included in the form and are not omitted/overwritten with custom form fields (#26)

# 1.2.2
## 2024-01-16

1. [](#bugfix)
    * design fix for the dropzone trigger (#19)
2. [](#improved)
    * readme clarification for frontmatter usage (#18)
    * readme clarification of not supporting nested array field names at the moment (#20)
  

# 1.2.1
## 2024-01-10

1. [](#bugfix)
    * newly added files could not have metadata files, because there was no filepath information
2. [](#improved)
    * set line breaks to LF instead of CRLF


# 1.2.0
## 2023-01-11

1. [](#new)
    * Added Support for files in flex-object data storage

# 1.1.0
## 2020-06-05

1. [](#new)
    * Page specific media metadata fields can be added to a page’s frontmatter (see README --> Configuration)
2. [](#improved)
    * meta.yaml file will not be created upon upload anymore: this allows the creation of the file with the setting media.auto_metadata_exif turned on (in Admin Plugin 1.9); in Admin Plugin 1.10 it did not work anyway – **NOTE: in Admin Plugin 1.10 with setting media.auto_metadata_exif turned on, make sure to save the page with a newly uploaded image before adding other metadata using this plugin**

# 1.0.2
## 2020-04-16

1. [](#improved)
    * The button next to a media file will now also be translated. It was hard-coded to English before.

# 1.0.1
## 2020-04-10

1. [](#bugfix)
    * meta.yaml file will now be created for an already uploaded media file that did not have an associated meta.yaml file before

# 1.0.0
## 2020-03-20

1. [](#improved)
    * using core technology (Grav\Common\Yaml) for parsing and writing the meta.yaml files – kudos to https://github.com/renards for adding crucial parts to the code

# 0.9.1
## 2020-03-16

1. [](#bugfix)
    * adding /vendor/ and composer.lock, needed for composer autoload functionality

# 0.9.0
## 2020-03-15

1. [](#new)
    * Initial release