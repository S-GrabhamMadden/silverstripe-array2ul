<%--
    ExpandableArrayList template.
    Located at: templates/Sunnysideup/ArrayToUl/View/ExpandableArrayList.ss

    Each row gets a `$TypeClass` (e.g. eal-type-num, eal-type-bool, …) so
    layout-level styling (right-aligning numbers, etc.) can target the
    whole row, not just the inner span.
--%>
<% if $IsRoot %>
<div class="eal $InstanceId<% if $StartExpanded %> is-expanded<% end_if %>">
$Styles
<% end_if %>

<% if $IsEmpty %>
    <span class="eal-empty">$EmptyLabel</span>
<% else %>

    <% if $NeedsCollapse %>
    <div class="eal-section<% if $StartExpanded %> is-expanded<% end_if %>">
    <% end_if %>

    <% if $IsAssoc %>
        <dl class="eal-list">
            <% loop $Items %>
            <div class="eal-row $TypeClass<% if $IsHidden %> eal-hidden<% end_if %>">
                <dt>$Key</dt>
                <dd>$Value</dd>
            </div>
            <% end_loop %>
        </dl>
    <% else %>
        <ul class="eal-list">
            <% loop $Items %>
            <li class="eal-row $TypeClass<% if $IsHidden %> eal-hidden<% end_if %>">$Value</li>
            <% end_loop %>
        </ul>
    <% end_if %>

    <% if $NeedsCollapse %>
        <button type="button" class="eal-toggle"
                aria-expanded="<% if $StartExpanded %>true<% else %>false<% end_if %>"
                data-count="$HiddenCount"
                onclick="$ToggleScript">
            <span class="eal-toggle-icon" aria-hidden="true"></span>
            <span class="eal-toggle-label"><% if $StartExpanded %>Show less<% else %>Show $HiddenCount more<% end_if %></span>
        </button>
    </div>
    <% end_if %>

<% end_if %>

<% if $IsRoot %>
</div>
<% end_if %>
