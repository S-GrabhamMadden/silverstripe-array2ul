# Array to UL — tl;dr

Render a PHP array (associative, indexed, or nested) as an expandable HTML
list. AJAX-safe: all CSS and JS are inline and scoped to a unique class per
instance, so it works wherever you drop it.

## Basic use

```php
use Sunnysideup\ArrayToUl\View\ExpandableArrayList;

$data = [
    'name'    => 'Widget',
    'price'   => 19.99,
    'inStock' => true,
    'tags'    => ['new', 'featured', 'sale'],
    'meta'    => [
        'sku'     => 'WGT-001',
        'weight'  => '250g',
    ],
];

echo ExpandableArrayList::create($data);
```

In a `.ss` template:

```ss
$MyList
```

## As a form field

```php
use Sunnysideup\ArrayToUl\View\ExpandableArrayListField;

public function getCMSFields()
{
    $fields = parent::getCMSFields();

    $fields->addFieldToTab(
        'Root.Debug',
        ExpandableArrayListField::create('RawData', $this->getRawData())
            ->setCollapseAfter(10)
            ->setTitle('Raw payload')
    );

    return $fields;
}
```

## Configuration

All setters are chainable and available on both classes:

| Setter                  | Default     | What it does                              |
|-------------------------|-------------|-------------------------------------------|
| `setCollapseAfter(int)` | `5`         | Show this many items, hide the rest behind a toggle. `0` disables collapsing. |
| `setStartExpanded(bool)`| `false`     | Render with everything visible up front.  |
| `setEmptyLabel(string)` | `'(empty)'` | Placeholder shown for empty arrays.       |
| `setData(array)`        | —           | Replace the array after construction.     |

## Notes

- **Associative arrays render as `<dl>`** with a two-column CSS-grid layout
  (key / value). Indexed arrays render as `<ul>` with custom bullets.
- **Nesting is recursive.** Each sub-array is rendered by its own inner
  `ExpandableArrayList` that inherits the parent's scoped CSS, so styles
  apply throughout but the `<style>` block is only emitted once.
- **Every collapsible list has its own toggle.** A long sub-array deep in
  the tree gets its own "Show N more" button, independent of the parent.
- **Multiple instances on one page** don't collide — each gets a random
  class like `eal-a1b2c3d4` and all CSS rules are prefixed with it.
- **AJAX-safe.** The toggle uses an inline `onclick`, not a `<script>` tag,
  because script tags inserted via `innerHTML` don't execute. Inline
  handlers always do.
- **Value formatting.** Booleans, `null`, empty strings, and objects each
  get a small styled `<span>` so they're visually distinguishable from
  regular string values. Strings are HTML-escaped via the template.
- **Theme override.** The template is resolved via `renderWith(self::class)`,
  so dropping a file at
  `themes/<theme>/templates/Sunnysideup/ArrayToUl/View/ExpandableArrayList.ss`
  overrides the markup without touching the PHP.
