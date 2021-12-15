# Static Markdown Pages

A tool for using static markdown pages inside your project. 

## Setup

```bash
composer require basteyy/static-markdown-pages
```

## Usage

First you need to have your markdown pages. In the following example I expect, that you have stored a couple of files inside `/var/www/storage/my_pages/`. 

```bash 
$ ls /var/www/storage/my_pages/
my_file_1.md
my_file_2.md
fancy-third-file.md
```


Every page needs to 
contain a few meta-data at the beginning of the document. 

For example content of `fancy-third-file.md`:

```bash
$ cat /var/www/storage/my_pages/fancy-third-file.md
```

```markdown
title: I'm the fancy third file!
url: /fancy-third/
author: John Doe
===

# Example Markdown Page

As you can see .. this is markdown
```


```php
/** @var \basteyy\StaticMarkdownPage\Archive $staticPages */
$staticPages = new \basteyy\StaticMarkdownPage\Archive('/var/www/storage/my_pages/');
```

To get the fancy-third-file, you need to pass the url to getByUrl-Method:

```php
/** @var \basteyy\StaticMarkdownPage\Archive $staticPages */
/** @var \basteyy\StaticMarkdownPage\Page $page */
$page = $staticPages->getByUrl('/fancy-third/');
```

Now you can print the file:

```php
/** @var \basteyy\StaticMarkdownPage\Archive $staticPages */
/** @var \basteyy\StaticMarkdownPage\Page $page */
echo $page->getHtml();
```

That's it.
