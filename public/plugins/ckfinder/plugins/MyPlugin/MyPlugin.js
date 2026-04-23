 CKFinder.define( function () {

     var MyPlugin = {
         init: function ( finder ) {
             finder.on( 'app:ready', function ( evt ) {
                 finder.request( 'dialog:info', {
                     title : '重要提醒',
                     msg: '<div style="text-align:center;width:450px;"><p>系統不支援中文檔名</p><h1 style="color:#FF0000;">請勿上傳中文檔名</h1></div>',
                     buttons : ['okClose']
                 } );
             } );
         }
     };

     return MyPlugin;
 } );