Manual
======

*copy from https://addons.nette.org/nifty/nifty-grid translated and keep updated*

Base usage
----------

Create Grid in separate class (eusability, clarity). Each Grid extends from base Grid `\NiftyGrid\Grid`.

> For simplicity we will read data directly from database without models. In your own application you should use model. Examples below are written for Nette\Database. 

For this example we have this database:

|id |category_id|user_id|title           |published          |status|views|
|---|-----------|-------|----------------|-------------------|------|-----|
|1  |10         |30     |Ford Mustang    |2015-08-12 12:33:45|1     |25   |
|2  |14         |26     |Chevrolet Camaro|2015-08-14 10:18:37|1     |18   |
|3  |8          |18     |Dodge Charger   |2015-08-15 14:45:55|0     |11   |


1. Create factory for Grid in presenter and give DB table to constructor.

```php
protected function createComponentArticleGrid()
{
    return new ArticleGrid($this->context->database->table('article'));
}
```

2. Create class with Grid. In method configure is all Grid settings. It has only one parameter Presenter which receive automatically from method Nette\Application\UI\PresenterComponent::attached(). 

```php
use \NiftyGrid\Grid;

class ArticleGrid extends Grid{

    protected $articles;

    public function __construct($articles)
    {
        parent::__construct();
        $this->articles = $articles;
    }

    protected function configure($presenter)
    {
        //Create data source for Grid
        //Allways select id
        $source = new \NiftyGrid\NDataSource($this->articles->select('article.id, title, status, views, published, category.name AS category_name, user.username, user.id AS user_id'));
        //Give source
        $this->setDataSource($source);
    }
}
```

3. Now you can create columns in method configure. Each column must have same name as name of column in DB table (or its alias).

```php
$this->addColumn('title', 'Title', '250px', 30);
$this->addColumn('username', 'Author', '100px');
$this->addColumn('published', 'Date', '100px');
$this->addColumn('status', 'Status', '100px');
$this->addColumn('views', 'Views', '100px');
```

First parameter (required) is name of column. Second parameter, label, is title of column. Third parameter is width of column - it can be in pixels or percents. Last parameter is truncation of text.     

Method addColumn returns instance of class `NiftyGrid\Components\Column`

Settings

```php
//for disabling pagination and display all records
$this->paginate = FALSE;

//disable sorting of all columns
$this->enableSorting = FALSE;

//sets width of whole grid
$this->setWidth('1000px');

//default ordering
$this->setDefaultOrder("article.id DESC");

//sets variants of per page records in selectbox
$this->setPerPageValues(array(10, 20, 50, 100));

//disable sorting of scecific column
$this->addColumn('column', 'Column')
    ->setSortable(FALSE);
    
//settings of templates
$this->setTemplate('path/to/my/grid-template.latte');
$this->getPaginator()->setTemplate('path/to/my/paginator-template.latte');
```

Result formating
----------------

Before rendering you can format result data. For example in column published you can have only date. Do it by method setRenderer which has one parameter - anonymous function with parameter specific row. 

```php
$this->addColumn('published', 'Date', '100px')
            ->setRenderer(function($row){return date('j.n.Y', strtotime($row['published']));})
```

You can render html tags with Nette\Utils\Html::el() e.g. link to author's profile  

```php
$this->addColumn('username', 'Author', '100px')
        ->setRenderer(function($row) use ($presenter){return \Nette\Utils\Html::el('a')->setText($row['username'])->href($presenter->link("user", $row['user_id']));});
```

Cell formating
--------------

If you want to apply css style of cell according cells's value - you can use set CellRendering 

```php
$this->addColumn('views', 'Views', '100px')
    ->setCellRenderer(function($row){return $row['views'] > 80 ? "background-color:#E4FFCC" : NULL;});
```

Row actions
-----------
`NiftyGrid\Components\Button`    

For adding row action use method addButton. First parameter is name of button, second parameter is label (HTML attribute title). 
Text of button you can define by method addText. Then you can define css class by method setClass, link by setLink, target by setTarget and confirmation dialog by setConfirmationDialog. 

```php
$this->addButton("delete", "Remove item")
    ->setClass("btn btn-danger")
    ->setText("Del")
    ->setLink(function($row) {return $this->link("delete!", $row['id']);})
    ->setConfirmationDialog(function($row){return "Are you sure to remove article $row[title]?";});
```

> **Notice:**   
> There is a problem with confirmation dialog (JS function confirm()), if you discard confirmation dialog AJAX request will be proceed anyway.     
> This is solved in `assets/js/grid.ajax.js` file. If you use your own script don't forget fix this problem. Easiest solution is disable AJAX (see below) on columns with confirmation dialog.                 

If you don't use AJAX, e.g. redirect to another presenter, use method setAjax(FALSE).

```php
$this->addButton(...)
    ->setAjax(FALSE);
```

Text of button can be HTML code:   
```php
$this->addButton(...)
    ->setText(Nette\Utils\Html::el('span')->setClass('glyphicon glyphicon-pencil'));  // <span class="glyphicon glyphicon-pencil"></span>
```

You can create action with different functions depending on row value. For example for publish/unpublish article.

```php
$this->addButton("publish")
	->setClass(function ($row) {return $row['status'] === 1 ? "btn btn-danger" : "btn btn-success";})
    ->setLabel(function ($row) {return $row['status'] === 1 ? "Unpublish" : "Publish";})
    ->setLink(function($row) {return $row['status'] === 1 ? $this->link("unpublish!", $row['id']) : $this->link("publish!", $row['id']);});
```

And display of button you can also control by anonymous function.

```php
$this->addButton("delete")
	->setShow(function($row) use ($presenter) {return $presenter->getUser()->isAllowed('Articles', 'delete');})
    ...
```

Mass actions
------------
`NiftyGrid\Components\Action`   

For adding mass action use method addAction. Parameter id in method setCallback contains array with id of selected columns.

```php
$this->addAction("publish","Publish")
    ->setCallback(function($id) {return $this->publish($id);});
```

You can use confirmation dialog for mass action.

```php
$this->addAction("delete","Remove")
    ->setCallback(function($id) {return $this->delete($id);})
    ->setConfirmationDialog("Are you sure to remove all selected articles?");
```

Row editing
-----------

To enable row editing you must use row action with predefined constant. 

```php
$this->addButton(Grid::ROW_FORM, "Fast edit")
    ->setClass("btn btn-primary")
	->setText("Edit");
```

Now the button for editing is active but no columns are marked as editable yet. For this use methods callable on columns.  

```php
//textEditable is for text and number values
$this->addColumn(...)->setTextEditable();
$this->addColumn(...)->setDateEditable();
//in case of selectEditable the grid automaticaly try set default value according to array $values and allows editing attached tables
$this->addColumn(...)->setSelectEditable(array $values, $prompt)
$this->addColumn(...)->setBooleanEditable();

//for formating value in edit form
$this->addColumn(...)->setFormRenderer($callback);
```

For getting and saving values you must set callback in method configure.

```php
$this->setRowFormCallback(function($values){
    //db update, flash message, ..
);
```

Global action
-------------
`NiftyGrid\Components\GlobalButton`   

Global action is basically global button with random link. It's defined by method addClobalButton and usage is similar like row action `NiftyGrid\Components\Button`.

```php
$this->addGlobalButton("export", "Exports data to CSV file")
	->setClass('btn btn-default')
	->setText('Export')
	->setLink(function() {return $this->link("export!");});  // notice that anonymous function doesn't have parameter $row - that's global action, not depend on any row
```

The most frequent use is for adding new record to database. This is very simple if there is enabled row editing - just set predefined constant ADD_ROW as first parameter:

```php
	$this->addGlobalButton(self::ADD_ROW, "Add new record")
		->setClass(...)
		->setText(...);
```

Filtering
---------

For filtering use 5 methods bellow

```php
setTextFilter();
setNumericFilter();
setDateFilter();
setSelectFilter(array $values, $prompt);
setMultiSelectFilter(array $values);
setBooleanFilter();
```
> In case of multiselect you can use attached javascript `assets/js/grid.multiselect.js` 

If you want to filter data from column of joined table use method setTableName("table.column") on column.

```php
$dataSource = new NDataSource($articles->select("article.title, user.username AS username"));
...
$this->addColumn("username", "User")
        ->setTableName("user.username")
        ->setTextFilter();
```

Filtering of **text**

|entering to input|filter
|---|---|
|text	|contains  "text" |
|text%	|starts with  "text"|
|%text	|ends with  "text" |

Filtering of **number** and **date**

|entering to input|filter
|---|---|
|1	|equal 1|
|>1	|greater then 1|
|>=1|greater or equal 1|
|<1	|smaller then 1|
|<=1|smaller or equal 1|
|<>1|not equal 1|


Autocomplete
------------

You can use autocomplete only for text filter. It must be called after filter method.
First parameter is number of searched records second parameter sets search mode:       
* constant `FilterCondition::STARTSWITH` (default) "suggests" words starting with entering string
* constant `FilterCondition::CONTAINS` "suggests" words contains entering string - usable especially with name/surname (Jack Daniels is suggested for "jac" or "dan") 

```php
$this->addColumn('name', 'Full name')
    ->setTextFilter()
    ->setAutocomplete(15, FilterCondition::CONTAINS);
```

SubGrids
--------
`NiftyGrid\Components\SubGrid`   

Each Grid can have more SubGrids. Each SubGrid can have more SubGrids. Adding SubGrid is very simple:

```php
$this->addSubGrid("comments", "Show comments of article")
    ->setGrid(new CommentGridByArticleId($presenter->context->database->table('comment'), $this->activeSubGridId))
    ->settings(function($grid){
        $grid->setWidth("800px");
        $grid['columns-title']->setWidth("400px");
    })
    ->setCellStyle("background-color:#f6f6f6; padding:20px;");
```

You give another predefined instance of Grid to method setGrid. Second parameter is $this->activeSubGridId - this is id of row which is parent of SubGrid - it is automatically set.  

Simple Grid CommentGridByArticleId: 

```php
use \NiftyGrid\Grid;

class CommentGridByArticleId extends Grid
{
    protected $comments;

    protected $article_id;

    public function __construct($comments, $article_id)
    {
        parent::__construct();
        $this->comments = $comments;
        $this->article_id = $article_id;
    }

    protected function configure($presenter)
    {
        $source = new \NiftyGrid\NDataSource($this->comments->select('comment.id, title, user.username')->where('article_id = ?', $this->article_id));

        $this->setDataSource($source);

        $this->addColumn("title", "Title");
        $this->addColumn("username", "Author");
    }

}
```
