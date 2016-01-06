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
For adding row action use method addButton. First parameter is name of button, second parameter is label. Then you can define css class by method setClass, link by setLink and confirmation dialog by setConfirmationDialog. 

```php
$self = $this;

$this->addButton("delete", "Remove")
    ->setClass("delete")
    ->setLink(function($row) use ($self){return $self->link("delete!", $row['id']);})
    ->setConfirmationDialog(function($row){return "Are you sure to remove article $row[title]?";});
```

If you don't use AJAX, e.g. redirect to another presenter, use method setAjax(FALSE).

```php
$this->addButton("edit", "Edit")
    ->setClass("edit")
    ->setLink(function($row) use ($presenter){return $presenter->link("article:edit", $row['id']);})
    ->setAjax(FALSE);
```

You can create action with different functions depending on row value. For example for publish/unpublish article.

```php
$this->addButton("publish")
    ->setLabel(function ($row) use ($self) {return $row['status'] === 1 ? "Unpublish" : "Publish";})
    ->setLink(function($row) use ($self){return $row['status'] === 1 ? $self->link("unpublish!", $row['id']) : $self->link("publish!", $row['id']);})
    ->setClass(function ($row) use ($self) {return $row['status'] === 1 ? "unpublish" : "publish";});
```

Mass actions
------------

For adding mass action use method addAction. Parameter id in method setCallback contains array with id of selected columns.

```php
$this->addAction("publish","Publish")
    ->setCallback(function($id) use ($self){return $self->handlePublish($id);});
```

You can use confirmation dialog for mass action.

```php
$this->addAction("delete","Remove")
    ->setCallback(function($id) use ($self){return $self->handleDelete($id);})
    ->setConfirmationDialog("Are you sure to remove all selected articles?");
```

Row editing
-----------

To enable row editing you must use row action with predefined constant. 

```php
$this->addButton(Grid::ROW_FORM, "Fast edit")
    ->setClass("fast-edit");
```

Now the button for editing is active but no columns are marked as editable yet. For this use methods callable on columns.  

```php
//textEditable is for text and number values
setTextEditable();
setDateEditable();
//in case of selectEditable the grid automaticaly try set default value according to array $values and allows editing attached tables
setSelectEditable(array $values, $prompt)
setBooleanEditable();

//for formating value in edit form
setFormRenderer($callback);
```

For getting and saving values you must set callback in method configure.

```php
$this->setRowFormCallback(function($values){
    //db update, flash message, ..
);
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
