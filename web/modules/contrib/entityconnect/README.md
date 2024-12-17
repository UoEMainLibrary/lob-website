# Entity connect

Entity Connect extends Entity References fields by adding "add new content" and
"edit current content" buttons.

Entity Connect expands upon any EntityReference field storage field. (Note that
this currently does not include base fields) by adding "add new content" and
"edit current content" buttons.

It will work with all default widgets provided by Entity reference.

The Drupal module will allow you to dynamically create and edit entities
which should be referenced by an Entity reference field.

It will help you to improve the User Experience for creation of new entities or
edition of linked entities on your entity reference fields.

The "add" button will enable you to add / create a new entity. The first
form will be cached then the new form will be generated. Once the new form is
completed and saved the user will return to the original form.

The "edit" button will take the user to the edit form of the referenced entity.
Once the user is done editing, they are returned to the previous form.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/entityconnect).

To submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/entityconnect).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

- Define permissions on admin/people/permissions page
- Define default parameters in administration page
  admin/config/content/entityconnect
- If you set default button visibility to ON
    * Go to a form (add or edit form) which contain an entityreference field and
      you should see an add (+) and/or edit (pencil) button after your field.
- If you set default button visibility to OFF
    * Go to fields administration for the entity you want to alter
      (eg: admin/structure/types/manage/page/fields)
- Edit an entityreference field and activate it for that field
  (Display Entity Connect "edit" button / Display Entity Connect "add" button)


## Maintainers

- jygastaud - [jygastaud](https://www.drupal.org/u/jygastaud)
- olmeta.david - [olmetadavid](https://www.drupal.org/u/olmetadavid)
- Agnes Chisholm - [amaria](https://www.drupal.org/u/amaria)
- Viktor Holovachek - [AstonVictor](https://www.drupal.org/u/astonvictor)
