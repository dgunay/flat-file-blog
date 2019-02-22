# Flat File Blog Backend

This is a simple blog backend for managing blog posts without a database.

The basic usage is that you write your posts in Markdown (with a little boilerplate
for additional metadata like tags, authorship, etc), drop them onto your server, and
then have this blog backend publish and organize the posts into something easy for it
to find.

## Why?

I rolled my own flat-file blog backend for my old website in procedural PHP. Now that 
I am rewriting the website in Symfony 4.2, I want to rewrite the blog backed in an OO
style to integrate better with the website. I am also decoupling the blog backend 
code by putting it in its own repository.

## Installation

To use it in your project, simply add a dependency on UPDATE/NAME
to your project's `composer.json` file. Here is a minimal example of a `composer.json`
file that just defines a dependency on UPDATE_NAME 1.x:

```json
{
    "require": {
        "dgunay/flat-file-blog": "~1.0"
    }
}
```

or 

`composer require dgunay/flat-file-blog`

## How It Works

There are 3 elements to the system:

1. A folder of unpublished posts
2. A folder of published posts
3. a JSON file that has the posts organized by publish time.