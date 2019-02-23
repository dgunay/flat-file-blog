# Flat File Blog Backend

This is a simple blog backend for managing blog posts without a database.

The basic usage is that you write your posts in Markdown (with a little boilerplate
for additional metadata like tags, authorship, etc), drop them onto your server, and
then have this blog backend publish and organize the posts into something easy for it
to find.

It does NOT generate any sort of view for your posts. It is up to you to decide
how you parse the Markdown file.

## Why?

I rolled my own flat-file blog backend for my old website in procedural PHP. 
Now that I am rewriting the website in Symfony 4.2, I want to rewrite the blog 
backend in an OO style to integrate better with the website. I am also 
decoupling the blog backend code from the website code by putting it in its own 
repository.

Finally, even though many other flat-file CMS's exist, I decided I wasn't 
totally reinventing the wheel because most of the other flat-file CMS's I looked 
at don't seem to have great headless options and this one just lets you decide
how you want to integrate it with your frontend.

## Installation

To use it in your project, simply add it as a dependency to your project's 
`composer.json` file. Here is an example of a `composer.json` file that does
that:

```json
{
    "require": {
        "dgunay/flat-file-blog": "~1.0"
    }
}
```

or 

```console
$ composer require dgunay/flat-file-blog
```

## How It Works

There are 3 elements to the system:

1. A folder of unpublished posts
2. A folder of published posts
3. a JSON file that has the published posts organized by publish time.

When you write a post, it looks something like:

```
<!--
author: 
tags: [ '#BigChungus', '#memes' ]
-->

# Title of My Blog Post

Lorem ipsum...
```

Metadata is in a YAML header bounded by an HTML comment. You may either define 
a `title` in the header or it will parse the first `#` header of your post.

You place your unpublished posts in a folder.

When you publish your post, the system copies your post with the publish time 
prepended to the filename as a unix timestamp. 

If you edit the contents of the file in the future, the system will keep track
of it so you can choose to display things like *last edited on blah blah*.

If you wish, you can have the system generate a JSON file that has the posts
sorted by publish time or by nested year/month/day. The system can use this to
find posts more quickly in the future.