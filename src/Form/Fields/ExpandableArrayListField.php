<?php

namespace Sunnysideup\ArrayToUl\Form\Fields;

use SilverStripe\Forms\LiteralField;
use Sunnysideup\ArrayToUl\View\ExpandableArrayList;

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
 *         ExpandableArrayListField::create('RawData', 'My Heading', $this->getRawData())
 *             ->setCollapseAfter(10)
 *             ->setTitle('Raw payload')
 *     );
 */
class ExpandableArrayListField extends LiteralField
{
    private ExpandableArrayList $list;

    protected $title = '';

    public function __construct(
        string $name,
        string $title = null,
        array $value = [],
        int $collapseAfter = 25,
        bool $startExpanded = false,
        string $emptyLabel = '(empty)'
    ) {
        $this->title = $title ?? '';
        $this->list = ExpandableArrayList::create(
            $value,
            $collapseAfter,
            $startExpanded,
            $emptyLabel
        );

        parent::__construct($name, 'content not set yet');
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

    public function setSummaryLabelKeys(array $keys): static
    {
        $this->list->setSummaryLabelKeys($keys);
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

    public function FieldHolder($properties = [])
    {
        $this->content = $this->glueContent();
        return parent::FieldHolder($properties);
    }

    public function Field($properties = [])
    {
        $this->content = $this->glueContent();
        return parent::Field($properties);
    }

    private bool $contentSet = false;

    public function glueContent(): string
    {
        if ($this->contentSet) {
            return $this->content;
        }
        $this->contentSet = true;
        $html = '';
        if ($this->title) {
            $html = '<h2>' . $this->title . '</h2>';
        }
        $html .=  $this->list->forTemplate();
        // Pass the renderer itself as LiteralField content. It's a
        // ViewableData, so LiteralField::Field() will hand it to the
        // form template, which calls forTemplate() on render.
        return $html;
    }
}
