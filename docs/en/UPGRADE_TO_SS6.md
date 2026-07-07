# Upgrade Guide: Moving to Silverstripe CMS 6

This document outlines the necessary changes to upgrade your project to be compatible with `sunnysideup/array2ul` for Silverstripe CMS 6.

## ⚠️ BREAKING CHANGE: Core Dependencies

The required versions of core Silverstripe modules have been updated.

- **`silverstripe/framework`**: has been upgraded from `^5.0` to `^6.0`.
- **`silverstripe/admin`**: has been upgraded from `^2.0` to `^3.0`.

You must update your project's `composer.json` to reflect these new requirements.

```json
"require": {
    "silverstripe/framework": "^6.0",
    "silverstripe/admin": "^3.0"
}
```

## ⚠️ BREAKING CHANGE: API Updates

Key classes have been updated to align with Silverstripe 6 conventions.

- The `Sunnysideup\ArrayToUl\View\ExpandableArrayList` class no longer extends `SilverStripe\View\ViewableData`. It now extends `SilverStripe\Model\ModelData` and implements the `Stringable` interface. If you have subclassed this class, you will need to update your implementation to reflect this change.

## Minor API Changes

To improve compatibility with modern PHP, the `#[Override]` attribute has been added to several methods. This change is internal and should not affect standard usage.

- `Sunnysideup\ArrayToUl\Form\Fields\ExpandableArrayListField`:
    - `setValue()`
    - `FieldHolder()`
    - `Field()`
- `Sunnysideup\ArrayToUl\View\ExpandableArrayList`:
    - `forTemplate()`
    - `__toString()`
