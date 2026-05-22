<?php

namespace Sunnysideup\ArrayToUl\View;

use DateTimeInterface;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBDecimal;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBFloat;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\ORM\FieldType\DBPercentage;
use SilverStripe\ORM\FieldType\DBTime;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;

/**
 * Renders a PHP array (associative, indexed, or nested) as an expandable
 * HTML list. Detects PHP scalar types AND SilverStripe DBField objects
 * and formats each appropriately:
 *
 *   - int / float / DBInt / DBFloat / DBDecimal / DBCurrency / DBPercentage
 *       → right-aligned, tabular monospace
 *   - bool / DBBoolean             → TRUE / FALSE
 *   - null                         → NULL
 *   - DateTimeInterface / DBDate / DBDatetime / DBTime
 *                                  → formatted date/time
 *   - HTML string / DBHTMLText / DBHTMLVarchar
 *                                  → raw HTML source in <pre><code>
 *   - long strings (> 200 chars)   → truncated with "click to expand"
 *   - everything else              → plain text, HTML-escaped
 *
 * Template:  templates/Sunnysideup/ArrayToUl/View/ExpandableArrayList.ss
 */
class ExpandableArrayList extends ViewableData
{
    private static $casting = [
        'EmptyLabel'   => 'Varchar',
        'Styles'       => 'HTMLFragment',
        'ToggleScript' => 'Varchar',
        'HiddenCount'  => 'Int',
    ];

    private array $data;
    private int $collapseAfter;
    private bool $startExpanded;
    private string $emptyLabel;
    private int $textTruncateAt;
    private string $instanceId;
    private bool $isRoot;
    private bool $allowHtmlAsIs = false;

    public function __construct(
        array $data = [],
        int $collapseAfter = 5,
        bool $startExpanded = false,
        string $emptyLabel = '(empty)',
        ?string $parentInstanceId = null,
        int $textTruncateAt = 200
    ) {
        parent::__construct();
        $this->data           = $data;
        $this->collapseAfter  = max(0, $collapseAfter);
        $this->startExpanded  = $startExpanded;
        $this->emptyLabel     = $emptyLabel;
        $this->textTruncateAt = max(0, $textTruncateAt);
        $this->isRoot         = $parentInstanceId === null;
        $this->instanceId     = $parentInstanceId ?? ('eal-' . bin2hex(random_bytes(4)));
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function setCollapseAfter(int $n): self
    {
        $this->collapseAfter = max(0, $n);
        return $this;
    }

    public function setStartExpanded(bool $value): self
    {
        $this->startExpanded = $value;
        return $this;
    }

    public function setEmptyLabel(string $label): self
    {
        $this->emptyLabel = $label;
        return $this;
    }

    /**
     * Maximum string length before a value gets truncated with a
     * click-to-expand toggle. Pass 0 to disable truncation entirely.
     */
    public function setTextTruncateAt(int $n): self
    {
        $this->textTruncateAt = max(0, $n);
        return $this;
    }

    public function setAllowHtmlAsIs(bool $allow): self
    {
        $this->allowHtmlAsIs = $allow;
        // No-op since we auto-detect HTML in strings. Method provided for API symmetry.
        return $this;
    }

    public function forTemplate()
    {
        return $this->renderWith(self::class);
    }

    public function __toString(): string
    {
        return (string)$this->forTemplate();
    }

    // ---------------------------------------------------------------------
    // Template accessors
    // ---------------------------------------------------------------------

    public function getInstanceId(): string
    {
        return $this->instanceId;
    }
    public function getIsRoot(): bool
    {
        return $this->isRoot;
    }
    public function getStartExpanded(): bool
    {
        return $this->startExpanded;
    }
    public function getIsEmpty(): bool
    {
        return $this->data === [];
    }
    public function getEmptyLabel(): string
    {
        return $this->emptyLabel;
    }
    public function getIsAssoc(): bool
    {
        return $this->isAssocInner($this->data);
    }
    public function getNeedsCollapse(): bool
    {
        return $this->collapseAfter > 0 && count($this->data) > $this->collapseAfter;
    }
    public function getHiddenCount(): int
    {
        return max(0, count($this->data) - $this->collapseAfter);
    }

    public function getItems(): ArrayList
    {
        $items         = ArrayList::create();
        $needsCollapse = $this->getNeedsCollapse();
        $i             = 0;

        foreach ($this->data as $key => $value) {
            $hidden = $needsCollapse && $i >= $this->collapseAfter && !$this->startExpanded;
            $items->push(ArrayData::create([
                'Key'       => (string)$key,
                'Value'     => $this->renderValue($value),
                'IsHidden'  => $hidden,
                'TypeClass' => $this->typeClass($value),
            ]));
            $i++;
        }

        return $items;
    }

    /**
     * Inline, scoped CSS. Emitted once by the root instance only.
     */
    public function getStyles(): string
    {
        if (!$this->isRoot) {
            return '';
        }

        $id  = $this->instanceId;
        $mono = 'ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace';
        $css = <<<CSS
.{$id}.eal{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;color:#1f2937;}
.{$id} .eal-list{margin:0;padding:0;list-style:none;}
.{$id} ul.eal-list{padding-left:1rem;}
.{$id} ul.eal-list>.eal-row{position:relative;padding:.2rem 0 .2rem .65rem;}
.{$id} ul.eal-list>.eal-row::before{content:"";position:absolute;left:0;top:.7em;width:.3rem;height:.3rem;border-radius:50%;background:#9ca3af;}
.{$id} dl.eal-list{display:grid;grid-template-columns:max-content 1fr;column-gap:1rem;row-gap:.15rem;}
.{$id} dl.eal-list>.eal-row{display:contents;}
.{$id} dl.eal-list>.eal-row>dt{font-weight:600;color:#374151;padding:.2rem 0;align-self:start;}
.{$id} dl.eal-list>.eal-row>dd{margin:0;padding:.2rem 0;min-width:0;word-break:break-word;}
.{$id} dl.eal-list>.eal-type-num>dd{text-align:right;font-family:{$mono};font-variant-numeric:tabular-nums;color:#0c4a6e;}
.{$id} dl.eal-list>.eal-type-date>dd{font-family:{$mono};font-variant-numeric:tabular-nums;color:#5b21b6;}
.{$id} .eal-list .eal-list,.{$id} .eal-list .eal-section{margin-top:.15rem;}
.{$id} .eal-hidden{display:none;}
.{$id} .eal-section.is-expanded>ul.eal-list>.eal-row.eal-hidden{display:list-item;}
.{$id} .eal-section.is-expanded>dl.eal-list>.eal-row.eal-hidden{display:contents;}
.{$id} .eal-toggle{display:inline-flex;align-items:center;gap:.4rem;margin-top:.4rem;padding:.3rem .7rem;background:#f3f4f6;border:1px solid #d1d5db;border-radius:.375rem;color:#374151;font:inherit;font-size:.8125rem;cursor:pointer;transition:background .15s,border-color .15s;}
.{$id} .eal-toggle:hover{background:#e5e7eb;border-color:#9ca3af;}
.{$id} .eal-toggle:focus-visible{outline:2px solid #2563eb;outline-offset:2px;}
.{$id} .eal-toggle-icon{display:inline-block;width:.45rem;height:.45rem;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:translateY(-.1rem) rotate(45deg);transition:transform .2s;}
.{$id} .eal-section.is-expanded .eal-toggle-icon{transform:translateY(.05rem) rotate(-135deg);}
.{$id} .eal-empty,.{$id} .eal-null{color:#9ca3af;font-style:italic;font-family:{$mono};font-size:.85em;letter-spacing:.05em;}
.{$id} .eal-bool{font-family:{$mono};font-size:.85em;font-weight:600;letter-spacing:.05em;}
.{$id} .eal-bool-true{color:#059669;}
.{$id} .eal-bool-false{color:#dc2626;}
.{$id} .eal-num{font-family:{$mono};font-variant-numeric:tabular-nums;color:#0c4a6e;}
.{$id} .eal-date{font-family:{$mono};font-variant-numeric:tabular-nums;color:#5b21b6;}
.{$id} .eal-obj{color:#7c3aed;font-style:italic;}
.{$id} pre.eal-html{margin:.1rem 0;padding:.5rem .65rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:.25rem;font-family:{$mono};font-size:.8125rem;color:#1f2937;white-space:pre-wrap;word-break:break-word;max-height:18em;overflow:auto;}
.{$id} pre.eal-html>code{font:inherit;color:inherit;background:none;padding:0;}
.{$id} .eal-trunc{cursor:pointer;border-bottom:1px dotted #9ca3af;}
.{$id} .eal-trunc:hover{background:#fef3c7;}
.{$id} .eal-trunc-ellipsis{color:#9ca3af;margin-left:.15rem;font-weight:600;}
CSS;

        $css = preg_replace('/\s*\n\s*/', '', $css);

        return '<style>' . $css . '</style>';
    }

    public function getToggleScript(): string
    {
        return "var s=this.parentElement,e=s.classList.toggle('is-expanded');"
             . "this.setAttribute('aria-expanded',e);"
             . "this.querySelector('.eal-toggle-label').textContent="
             . "e?'Show less':('Show '+this.dataset.count+' more');";
    }

    // ---------------------------------------------------------------------
    // Value rendering
    // ---------------------------------------------------------------------

    /**
     * Format a single value for the template.
     */
    /**
       * Determine the intrinsic logical type of the value to route it correctly.
       */
    private function determineType($value): string
    {
        if (is_array($value)) {
            return 'array';
        }
        if ($value instanceof DBField) {
            if ($value instanceof DBBoolean) {
                return 'bool';
            }
            if ($value instanceof DBInt
                || $value instanceof DBFloat
                || $value instanceof DBDecimal
                || $value instanceof DBCurrency
                || $value instanceof DBPercentage) {
                return 'num';
            }
            if ($value instanceof DBDate
                || $value instanceof DBDatetime
                || $value instanceof DBTime) {
                return 'date';
            }
            if ($value instanceof DBHTMLText
                || $value instanceof DBHTMLVarchar) {
                return 'html';
            }
            return 'string';
        }
        if ($value instanceof DateTimeInterface) {
            return 'date';
        }
        if (is_bool($value)) {
            return 'bool';
        }
        if ($value === null) {
            return 'null';
        }
        if (is_int($value) || is_float($value)) {
            return 'num';
        }
        if (is_string($value)) {
            if ($this->looksLikeHtml($value)) {
                return 'html';
            }
            return 'string';
        }
        if (is_object($value)) {
            return 'obj';
        }

        return 'other';
    }

    /**
     * What CSS class describes the *row* containing this value. Used for
     * layout-level styling like right-aligning numbers in the dl grid.
     */
    private function typeClass($value): string
    {
        return 'eal-type-' . $this->determineType($value);
    }

    /**
     * Format a single value for the template.
     */
    private function renderValue($value): DBField
    {
        $type = $this->determineType($value);

        if ($type === 'array') {
            $nested = ExpandableArrayList::create(
                $value,
                $this->collapseAfter,
                $this->startExpanded,
                $this->emptyLabel,
                $this->instanceId,
                $this->textTruncateAt
            );
            return DBField::create_field('HTMLFragment', (string)$nested->forTemplate());
        }

        if ($value instanceof DBField) {
            return $this->renderDBField($value);
        }

        if ($type === 'date') {
            return $this->wrapHtml('eal-date', $value->format('Y-m-d H:i:s'));
        }

        if ($type === 'bool') {
            $label = $value ? 'TRUE' : 'FALSE';
            $class = $value ? 'eal-bool eal-bool-true' : 'eal-bool eal-bool-false';
            return $this->wrapHtml($class, $label);
        }

        if ($type === 'null') {
            return $this->wrapHtml('eal-null', 'NULL');
        }

        if ($type === 'num') {
            return $this->wrapHtml('eal-num', (string)$value);
        }

        if ($type === 'obj') {
            $str = method_exists($value, '__toString') ? (string)$value : get_class($value);
            return $this->wrapHtml('eal-obj', $str);
        }

        if ($type === 'html') {
            return $this->renderHtmlSource((string)$value);
        }

        // Fallback for 'string' and 'other' types
        $strValue = (string)$value;
        if ($strValue === '') {
            return DBField::create_field('HTMLFragment', '<span class="eal-empty">""</span>');
        }
        if ($this->textTruncateAt > 0 && mb_strlen($strValue) > $this->textTruncateAt) {
            return $this->renderTruncatedText($strValue);
        }

        // Let the template auto-escape plain strings.
        return DBField::create_field('Varchar', $strValue);
    }

    /**
     * Dispatch a SilverStripe DBField to the right native-type renderer.
     */
    private function renderDBField(DBField $field): DBField
    {
        $raw = $field->getValue();
        $type = $this->determineType($field);

        if ($type === 'bool') {
            return $this->renderValue((bool)$raw);
        }

        if ($type === 'num') {
            if ($raw === null) {
                return $this->renderValue(null);
            }
            return $this->renderValue($field instanceof DBInt ? (int)$raw : (float)$raw);
        }

        if ($type === 'date') {
            if ($raw === null || $raw === '') {
                return $this->renderValue(null);
            }
            return $this->wrapHtml('eal-date', (string)$raw);
        }

        if ($type === 'html') {
            return $raw === null || $raw === ''
                ? $this->renderValue(null)
                : $this->renderHtmlSource((string)$raw);
        }

        // Generic DBField — treat as string.
        return $raw === null ? $this->renderValue(null) : $this->renderValue((string)$raw);
    }

    private function looksLikeHtml(string $str): bool
    {
        // Cheap heuristic: contains at least one tag-shaped token.
        return (bool)preg_match('/<[a-z][a-z0-9]*(\s[^<>]*)?>/i', $str);
    }

    private function renderHtmlSource(string $html): DBField
    {
        if ($this->allowHtmlAsIs) {
            // If we're allowed to render HTML as-is, bypass the <pre><code> wrapper and emit raw HTML.
            return DBField::create_field('HTMLFragment', $html);
        }
        return DBField::create_field(
            'HTMLFragment',
            '<pre class="eal-html"><code>'
            . htmlspecialchars($html, ENT_QUOTES, 'UTF-8')
            . '</code></pre>'
        );
    }

    /**
     * Render a long string truncated with a click-to-expand affordance.
     * The full text is stashed in a data attribute and swapped in via
     * inline JS (innerText assignment auto-escapes, so XSS-safe).
     */
    private function renderTruncatedText(string $text): DBField
    {
        $truncated = mb_substr($text, 0, $this->textTruncateAt);
        $fullEsc   = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $shortEsc  = htmlspecialchars($truncated, ENT_QUOTES, 'UTF-8');
        $js        = "this.textContent=this.dataset.full;"
                   . "this.classList.remove('eal-trunc');"
                   . "this.removeAttribute('title');"
                   . "this.removeAttribute('onclick');";

        return DBField::create_field(
            'HTMLFragment',
            '<span class="eal-trunc" title="Click to expand"'
            . ' data-full="' . $fullEsc . '"'
            . ' onclick="' . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . '">'
            . $shortEsc
            . '<span class="eal-trunc-ellipsis">…</span>'
            . '</span>'
        );
    }

    private function wrapHtml(string $cssClass, string $text): DBField
    {
        return DBField::create_field(
            'HTMLFragment',
            '<span class="' . $cssClass . '">'
            . htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
            . '</span>'
        );
    }

    private function isAssocInner(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
