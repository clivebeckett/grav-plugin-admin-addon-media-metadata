# Admin Addon Media Metadata Plugin

The **Admin Addon Media Metadata** Plugin is an extension for [Grav CMS](http://github.com/getgrav/grav) and. It lets you add and edit metadata for media files in the Page editing mode in the [Grav Admin plugin](https://github.com/getgrav/grav-plugin-admin).

The Admin plugin has not offered a feature like this. In order to add/edit metadata e.g. for an image you had to create a [image.filename].meta.yaml for the image in your file browser and edit it in your text editor.

## Usage and Features

- the plugin will create and edit [mediafile].meta.yaml files in your page folder via a simple form in the Admin plugin
- by default you can add/edit a **title,** **alt** text, and a **caption** – see Configuration below on how to adapt this for your Grav installation
- multiline text can be added (e.g. for caption)
- potential other data in your meta.yaml file will not be overwritten even if the form does not let you change it

### How to use it (once installed)

1. hover any file in your Page Media section
2. hit the small «i» button to open the metadata form *(the regular «i» button which just showed the metadata will be overwritten by the plugin)*

## Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `admin-addon-media-metadata`. You can find these files on [GitHub](https://github.com/clivebeckett/grav-plugin-admin-addon-media-metadata).

You should now have all the plugin files under

    /your/site/grav/user/plugins/admin-addon-media-metadata
	
> NOTE: This plugin is an addon for the [Grav Admin plugin](https://github.com/getgrav/grav-plugin-admin) and thus it won’t make much sense without the Admin plugin.

## Configuration

If you want to add more data to your meta.yaml files, please copy the  
`user/plugins/admin-addon-media-metadata/admin-addon-media-metadata.yaml` to  
`user/config/plugins/admin-addon-media-metadata.yaml`  
and add more fields to the form.

## Credits

I have based the plugin on Dávid Szabó’s [Admin Addon Media Rename plugin](https://github.com/david-szabo97/grav-plugin-admin-addon-media-rename). Much of the code would not have been possible for me without Dávid’s work.

## ToDo

- add the possibility for page-specific metadata forms.

![](assets/1-open-form.jpg)

![](assets/2-form-opened.jpg)
