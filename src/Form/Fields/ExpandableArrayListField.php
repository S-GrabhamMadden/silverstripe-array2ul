<?php

namespace Sunnysideup\ArrayToUl\View;

use SilverStripe\Forms\LiteralField;

/**
 * Form field that displays an array as an expandable HTML list.
 *
 * Wraps an {@see ExpandableArrayList} so it can be dropped into any
 * FieldList — getCMSFields(), a regular Form, or a readonly summary
 * screen — and rendered like any other field.
 *
 * Example:
 *
 *     $fields->addFieldToTab(
 *         'Root.Debug',
 *         ExpandableArrayListField::create('RawData', $this->getRawData())
 *             ->setCollapseAfter(10)
 *             ->setTitle('Raw payload')
 *     );
 */
class ExpandableArrayListField extends LiteralField
{
    private ExpandableArrayList $list;

    public function __construct(
        string $name,
        array $value = [],
        int $collapseAfter = 5,
        bool $startExpanded = false,
        string $emptyLabel = '(empty)'
    ) {
        $this->list = ExpandableArrayList::create(
            $value,
            $collapseAfter,
            $startExpanded,
            $emptyLabel
        );

        // Pass the renderer itself as LiteralField content. It's a
        // ViewableData, so LiteralField::Field() will hand it to the
        // form template, which calls forTemplate() on render.
        parent::__construct($name, $this->list);
    }

    /**
     * Replace the array shown by this field. Accepts an array; anything
     * else is passed through to the parent unchanged.
     */
    public function setValue($value, $data = null): static
    {
        if (is_array($value)) {
            // The LiteralField content already holds a reference to the
            // same list object, so mutating it is enough — no need to
            // re-assign $this->content.
            $this->list->setData($value);
        }
        return parent::setValue($value, $data);
    }

    public function setCollapseAfter(int $n): static
    {
        $this->list->setCollapseAfter($n);
        return $this;
    }

    public function setStartExpanded(bool $expanded): static
    {
        $this->list->setStartExpanded($expanded);
        return $this;
    }

    public function setEmptyLabel(string $label): static
    {
        $this->list->setEmptyLabel($label);
        return $this;
    }

    /**
     * Direct access to the underlying renderer for anything the
     * convenience setters don't cover.
     */
    public function getList(): ExpandableArrayList
    {
        return $this->list;
    }
}
