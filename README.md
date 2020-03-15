# Admin Addon Media Metadata Plugin

The **Admin Addon Media Metadata** Plugin is an extension for the [Grav CMS](http://github.com/getgrav/grav) [Admin plugin](https://github.com/getgrav/grav-plugin-admin). It lets you add and **edit metadata for media files** in the Page Media browser.

The Admin plugin has not been offering a feature like this yet. In order to add/edit metadata e.g. for an image you had to create a [image.filename].meta.yaml for the image in your file browser and edit it in a text editor.

## Usage and Features

- the plugin will create and edit [mediafile].meta.yaml files in your page folder via a simple form in the Admin plugin
- by default you can add/edit a **title,** **alt** text, and a **caption** – see *Configuration* section below on how to adapt this for your Grav installation
- multiline text can be added (e.g. for caption)
- potential other data in your meta.yaml file will not be overwritten even if the form does not let you change it

### How to use it

*(see also screenshots below)*

1. hover any file in your Page Media section
2. hit the small «i» button to open the metadata form *(the regular «i» button which just showed the metadata will be overwritten by the plugin)*

## Installation

To install the plugin manually, download the ZIP version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `admin-addon-media-metadata`. You can find these files on [GitHub](https://github.com/clivebeckett/grav-plugin-admin-addon-media-metadata).

Once the plugin is out of its beta state I will try and submit it to the Grav repository for installation via Grav’s package manager or via the Admin plugin.

## Configuration

If you want to add more data to your meta.yaml files, please copy the  
`user/plugins/admin-addon-media-metadata/admin-addon-media-metadata.yaml` to  
`user/config/plugins/admin-addon-media-metadata.yaml`  
and add more fields to the form by updating the copy.

## Credits

I have based the plugin on Dávid Szabó’s [Admin Addon Media Rename plugin](https://github.com/david-szabo97/grav-plugin-admin-addon-media-rename). Much of the code would not have been possible for me without Dávid’s work.

## ToDo

- add the possibility for page-specific metadata forms
- replace the small YAML file parser with Grav core technology

## Screenshots

![](assets/1-open-form.jpg)

![](assets/2-form-opened.jpg)
