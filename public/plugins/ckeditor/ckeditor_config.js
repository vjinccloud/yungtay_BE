/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	//工具列
	config.toolbarGroups = [
		{ name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
		{ name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
		{ name: 'forms', groups: [ 'forms' ] },
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		'/',
		{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
		{ name: 'links', groups: [ 'links' ] },
		{ name: 'insert', groups: [ 'insert' ] },
		'/',
		{ name: 'styles', groups: [ 'styles' ] },
		{ name: 'colors', groups: [ 'colors' ] },
		{ name: 'tools', groups: [ 'tools' ] },
		{ name: 'others', groups: [ 'others' ] ,items: [ 'Youtube' ]},
		{ name: 'about', groups: [ 'about' ] }
	];

	//移除按紐
	config.removeButtons = 'Save,NewPage,Preview,Print,Replace,Find,Scayt,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Language,Flash,Smiley,PageBreak,ShowBlocks,About,SelectAll';
	//避免ckeditor自動換程式碼
	config.allowedContent=true;
	//字體大小
	config.fontSize_sizes = '12/12px;13/13px;16/16px;18/18px;20/20px;22/22px;24/24px;36/36px;48/48px;';
	//字型設定
	config.font_names = '新細明體;細明體;標楷體;微軟正黑體;Arial;Arial Black;Comic Sans MS;Courier New;Georgia;Lucida Sans Unicode;Tahoma;Times New Roman;Verdana;';
	//外掛引用
	config.extraPlugins = 'uploadwidget,filetools,notificationaggregator,notification,uploadimage,lineutils,widget,widgetselection,image2,youtube';

};
