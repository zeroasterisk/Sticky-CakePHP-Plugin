# StickyableBehavior - packaged as a CakePHP Plugin


## Install

Put in place: `app/Plugin/Sticky`

    git submodule add https://github.com/zeroasterisk/Sticky-CakePHP-Plugin.git app/Plugin/Sticky

or

    cd app/Plugin
    git clone https://github.com/zeroasterisk/Sticky-CakePHP-Plugin.git Sticky

then add in `app/Config/bootstrap.php`

    CakePlugin::load('Sticky');

finally, put this **above** Containable on any Model you want to use it on
(or in AppModel to use on all Models)

    /**
    * behavior attachments (global)
    */
    public $actsAs = array(
      'Sticky.Stickyable',
      'Containable',
    );

## Usage: Setup 'contain' and 'joins' for 'find()'

So in this example, you could eaisly just pass in all of these details via find
`$options`... assuming all of these methods were done in line...

But lets assume that the logic is broken up into sub-functions in a variety of
places (on the model, on the controller, in a Behavior... wherever)

    $this->Post->recursive = -1;
    ... some sort of logic ...
    $this->Post->addStickyContain(array(
      'Comment' => array(
        'conditions' => array('created >' => date('Y-m-d', strtotime('yesterday')))
      ),
      'Author' => array('id', 'name', 'email'),
    ));
    ... some sort of logic ...
    $this->Post->addStickyContain(array(
      'Author' => array(
        'fields' => array('id', 'name', 'email'),
        'conditions' => array('email LIKE' => 'someone%'),
      )
    ));

    $postsWithRecentComments = $this->Post->find('count', array(
      'contain' => array(),
      'conditions' => array('Author.is_active' => true),
    ));
    $postsWithRecentComments = $this->Post->find('all', array(
      'contain' => array(),
      'conditions' => array('Author.is_active' => true),
      'limit' => 5,
    ));



## Why / Background

First, a big shout-out to the CakeDC Search plugin:

* https://github.com/CakeDC/search

That is a wonderful to keep your search filters organized and arranged into
managable chunks.  It is a direct result of working with that tool which
prompted me to write this.

When creating a `type=query`, I wanted to be able to modify the 'contain' or 'joins'
of the subsequent `find()` - but there was no way to do so.

I could use the `$Model->contain()` but that resets all the contains, and it
only lasts for the "next" query, not all times these conditions might be used
(pagination requires 2 finds).

## Attribution

As mentioned already, thanks to CakeDC for their Search plugin:

* https://github.com/CakeDC/search

...

and of course, you... pull requests welcome!

## License

This code is licensed under the MIT License


Copyright (C) 2013--2014 Alan Blount <alan@zeroasterisk.com> https://github.com/zeroasterisk/

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

