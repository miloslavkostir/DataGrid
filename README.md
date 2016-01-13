# DataGrid
DataGrid for Nette Framework based on Niftyx/NiftyGrid (it seems to be no longer supported).

This is extended version of NiftyGrid - a simple and light-weight **datagrid** for *Nette framework*.
This is optimalized for Nette >= v2.1. Support for Twitter Bootstrap 2 and 3.


## Install
```pre
	composer require miloslavkostir/datagrid
```    
Copy resources:   
* `assets/css/grid.css`
* `assets/js/grid.js`

to your public www dir and include them in template (usually in `@layout.latte`). grid.js needs jQuery and jQuery UI - download them from original source or use files from `assets/`.   
For AJAX use `assets/js/grid.ajax.js`. You can use some third party ajax addon instead, e.g. [nette.ajax.js](http://addons.nette.org/vojtech-dobes/nette-ajax-js):

```js
// just add selector .grid-ajax
$.nette.init(function (ajaxHandler) {
	$('.grid-ajax').on('click', ajaxHandler);
});
```
> **Notice:**   
> There is a problem with JS confirm(), if you discard confirmation dialog AJAX request will be proceed anyway.   
> This is solved in `assets/js/grid.ajax.js` file. If you don't use it you will probably have to create own solution.   
> See [manual](./manual.en.md#4) (section Row actions) for more informations.    

## Usage

See manual [en](./manual.en.md) [cz](./manual.cs.md)
