(function(){
var f = document.getElementsByTagName('form');
f = f && f.length && f[f.length-1]
if (!f || f.<?php echo $fieldname ?>) return
setTimeout(function(){
var i = document.createElement('input')
i.setAttribute('type','hidden')
i.setAttribute('name','<?php echo $fieldname ?>')
i.setAttribute('value','<?php echo $magic ?>;' + (new Date()/1000).toFixed())
f.appendChild(i)
/*@cc_on @*/
/*@if (@_jscript_version < 5.9)
	i.name = '<?php echo $fieldname ?>';
	i.parentNode.removeChild(i); f.innerHTML += (''+i.outerHTML).replace(/>/,' name="<?php echo $fieldname ?>">');
/*@end @*/
var dclick,o = f.onsubmit
f.onsubmit = function()
{
	if (dclick) return false
	if (this.elements.<?php echo $fieldname ?>) this.elements.<?php echo $fieldname ?>.value += ';' + (new Date()/1000).toFixed()
	if (!o || false !== o()) {dclick=true;setTimeout(function(){dclick=false},4000); return true}
	return false;
}
},1000)
})()
