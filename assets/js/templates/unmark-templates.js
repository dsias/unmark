if (unmark === undefined) { var unmark = {}; }

unmark.template = unmark.template || {};

//unmark.template.sidebar = '<header class="sidebar-action"><h4 class="sidebar-heading">Edit Mark</h4><a class="close-sidebar action" data-action="sidebar_collapse" href="#"><i class="icon-heading_close"></i></a></header><main><section class="mark-url"><h4 class="section-heading">URL</h4><div class="button-field"><input type="text" value="{{url}}" readonly="readonly"><a href="#" class="copy-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 34"><rect x="13" y="14" width="7" height="9"/><polygon points="11 12 15 12 15 9 8 9 8 18 11 18 11 12"/></svg></a></div></section><div class="sidebar-label label-{{label_id}}"><span id="label-chosen"></span><a class="action" data-action="marks_addLabel" href="#" data-id="{{mark_id}}">{{label_name}}</a><ul class="sidebar-label-list" data-id="{{mark_id}}"></ul></div><div class="sidebar-info-panel">{{#embed}}<h4 class="prev-coll">Preview <i class="icon-up"></i></h4><section class="sidebar-info-preview">{{{embed}}}</section>{{/embed}}<h4 class="action" data-action="marks_editMarkInfo">Notes (click to edit)<i class="icon-edit"></i></h4><section id="notes-{{mark_id}}" data-id="{{mark_id}}" class="sidebar-info-notes hideoutline">{{{notes}}}</section></div><button data-id="{{mark_id}}" data-view="sidebar" data-action="delete_mark">Delete Link</button>    </main>';



unmark.template.sidebar = '<header class="sidebar-action">'+
'<h4 class="sidebar-heading">Edit Mark</h4>'+
'<a class="close-sidebar action" data-action="sidebar_collapse" href="#"><i class="icon-heading_close"></i></a>'+
'</header>'+
'<main>'+
'<section class="mark-url">'+
'<h4 section-heading>URL</h4>'+
'<div class="button-field">'+
'<input type="text" value="{{url}}" readonly="readonly">'+
'<a href="#" class="copy-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 34"><rect x="13" y="14" width="7" height="9"/><polygon points="11 12 15 12 15 9 8 9 8 18 11 18 11 12"/></svg></a>'+
'</div>'+
'</section>'+
'<div class="sidebar-label label-{{label_id}}">'+
'<span id="label-chosen"></span>'+
'<a class="action" data-action="marks_addLabel" href="#" data-id="{{mark_id}}">{{label_name}}</a>'+
'<ul class="sidebar-label-list" data-id="{{mark_id}}"></ul>'+
'</div>'+
'<div class="sidebar-info-panel">'+
'{{#embed}}'+
'<h4 class="prev-coll">Preview <i class="icon-up"></i></h4>'+
'<section class="sidebar-info-preview">{{{embed}}}</section>'+
'{{/embed}}'+
''+
'<h4>Tags</h4>'+
'<section id="tags-{{mark_id}}"><input data-mark-id="{{mark_id}}" type="text" id="input-tags" class="selectize" value="{{tags_string}}"></section>'+
''+
'<!--<h4 class="action" data-action="marks_editMarkInfo">Notes (click to edit) <i class="icon-edit"></i></h4>-->'+
'<h4 class="action">Notes</h4>'+
'<textarea id="notes-{{mark_id}}" data-id="{{mark_id}}" placeholder="<?php echo unmark_phrase(\'Type note text or #tags here...\'); ?>" class="sidebar-info-notes">{{{notes}}}</textarea>'+
''+
'</div>'+
'<button data-id="{{mark_id}}" data-view="sidebar" data-action="delete_mark">Delete Link</button>'+
'</main>';

unmark.template.marks = '<div id="mark-{{mark_id}}" class="mark label-{{label_id}}"> <h2 class="hideoutline"><a target="_blank" rel="noopener noreferrer" href="{{url}}">{{title}}</a></h2> <div class="mark-meta"> <span class="mark-date">{{nice_time}}</span> <span class="mark-sep">•</span> <span class="mark-link"><a target="_blank" rel="noopener noreferrer" href="{{url}}">{{prettyurl}}</a></span> </div> <div class="archive-target"> {{#archived_on}} <a title="Unarchive Mark" class="action mark-archive" data-action="mark_restore" href="#" data-id="{{mark_id}}"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><rect x="6" y="28" width="2" height="20"/><rect x="38" y="28" width="2" height="20"/><rect x="29.74" y="-2.79" width="2.06" height="24" transform="translate(2.5 24.45) rotate(-45)"/><rect x="14.18" y="-2.68" width="2.36" height="24" transform="translate(19.63 26.77) rotate(-135)"/><line x1="23.01" x2="39.98" y2="16.97"/><rect x="22" y="3" width="2" height="33"/><rect x="6" y="46" width="34" height="2"/></svg> </a> {{/archived_on}} {{^archived_on}} <a title="Archive Mark" class="action mark-archive" data-action="mark_archive" href="#" data-id="{{mark_id}}"> <i> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><rect x="25.8" y="6.95" width="4" height="30.5" transform="translate(23.84 -13.16) rotate(45)"/><rect x="12.16" y="22.45" width="4" height="13.43" transform="translate(-16.47 18.56) rotate(-45)"/></svg> </i> </a> {{/archived_on}} </div> <div class="mark-actions"> <a title="View Mark Info" class="action mark-info" href="#" data-nofade="true" data-action="show_mark_info" data-mark="mark-data-{{mark_id}}"> <i> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 8"><circle cx="18" cy="4" r="4"/><circle cx="32" cy="4" r="4"/><circle cx="4" cy="4" r="4"/></svg> </i> </a> </div> <div class="note-placeholder"></div> <script id="mark-data-{{mark_id}}" type="application/json">{"mark_id":"{{mark_id}}","label_id":"{{label_id}}","label_name":"{{label_name}}","notes":"{{notes}}","archived":{{active}}}</script> </div>';
