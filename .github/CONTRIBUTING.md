Contributing to Bolt
====================

If you are reading this, you must be considering opening an issue for help with
a bug, to suggest a new feature or help out with a Pull Request. Great!

For questions about working with Bolt or support issues, please post on the
[forum](https://discuss.bolt.cm) instead. If you post these kinds of topics there,
we can keep the _issues_ list organized, with actual issues that need to be
handled in the Bolt source code itself.

To streamline this process, we've made this small document, to help you report
your issue or feature more efficiently. We did our best to keep it brief, so
please read it carefully.

Jump to the relevant section below:

 - [1. I'm reporting a bug](#1-im-reporting-a-bug)
 - [2. I'd like to request/propose a feature](#2-id-like-to-requestpropose-a-feature)
 - [3. I'm making a pull request](#3-im-making-a-pull-request)

**Note:** Please don't use our main GitHub repository for reporting bugs
with _extensions_. If you have found a bug in an extension, the best place to
report this would be in the extension's own repository, so that the original
author can look into it.

1. I'm reporting a bug
----------------------

If you think you've found a bug, try to make sure it's actually a bug, and not
the result of something else going wrong. For example, make sure your web server
is functioning correctly, and try the issue in another browser, if possible. Be
sure to **search for similar bugs**. Perhaps somebody has already reported the
issue. If so, you should add any additional information to the existing bug. It
might make it easier for us to fix, and at the very least it'll get bumped
higher onto the list.

After you've done these things, and you are fairly certain you've found a new
bug, please open an issue to report it. Create a [GitHub account](https://github.com),
if you don't have one yet.

When posting your bug, please include the following:

 1. **Bug summary**: 
    * Write a short summary of the bug​​
    * Try to pinpoint it as muc​​h a possible
    * Try to state the _actual problem_, and not just what you _think_ the 
      solution might be.
 2. **Specifics**:
    * Mention the URL where this bug occurs, if applicable
    * What version of Bolt are you using (down to the very last digit!)
    * What method did you use to install Bolt
    * What browser and version you are using
    * Please mention if you've checked it in other browsers as well 
    * Please include *full error messages* and *screenshots* if possible
 3. **Steps to reproduce**:
    * Clearly mention the steps to reproduce the bug
 4. **Expected result**: 
    * What did you _expect_ that would happen on your Bolt site?
    * Describe the intended outcome after you did the steps mentioned before
 5. **Actual result**: 
    * What is the actual result of the above steps? 
    * Describe the behaviour of the bug 
    * Please, please include **error messages** and screenshots. They might mean 
      nothing to you, but they are _very_ helpful to us.
    to us.

Further reading: [10 tips for better Pull Requests](http://blog.ploeh.dk/2015/01/15/10-tips-for-better-pull-requests/)

### Reporting security issues

If you wish to contact us privately about any possible security issues in Bolt,
please contact us at [security@bolt.cm](mailto:security@bolt.cm). Your email
will be handled confidentially. After fixing them, you will be credited for any
security issues that you may have discovered.


2. I'd like to request/propose a feature
----------------------------------------

We get a lot of feature requests for Bolt, and we can't do them all, even if we
wanted to. In fact, since Bolt is designed to be 'simple, sophisticated and
straightforward', not all feature requests might be a good fit for Bolt. Be sure
to read [our manifesto](https://docs.bolt.cm/manifesto) to get an idea of our
goals and values.

If you're proposing an idea, with the intent to work on it yourself, please open
an issue with a title like `[RFC] Wouldn't it be swell, if we had [X]?`. That
will make it obvious that you're open for discussion about the feature, and that
it'll be a good fit for Bolt itself, or that it might be better suited as an
extension. If you want to discuss your idea first, before 'officially' posting
it, you can always join us on [IRC](http://bolt.cm/community).

On the other hand, if you have a suggestion for a cool new feature, but don't
have the skill to work on it yourself, we'll gladly read it, and comment on it,
but we can't make any promises as to when/if it'll be realised.


3. I'm making a pull request
----------------------------

Your contributions to the project are very welcome. If you would like to fix a
bug or implement a proposed feature, you can submit a Pull Request.

Make sure you read our guide on [Contributing to
Bolt](https://docs.bolt.cm/internals/contributing), the page on [Code
Quality](https://docs.bolt.cm/internals/code-quality) and the page describing
our [Release process](https://docs.bolt.cm/internals/release-process).

### Bug fixes & "mini" features

To help us merge your Pull Request, please make sure you follow these points:

 1. Describe the problem clearly in the Pull Request description.
 2. Please refer to a bug report on the issue list. An example of a good title
    is "Fixes problem [x] by doing [y]. Fixes #1555"
 4. Do not edit compiled asset files such as `bolt.js` or `bolt.css`.
    Instead, edit the source JavaScript and Sass files inside the `/app/src/`
    directory. We'll handle the updated compiled files.
 5. For any change that you make, **please try to also add a test case(s)** in
    the `tests` directory.
 3. If you're doing a PR for a non-trivial new feature, see the [Features](#features)
    section below.

### Features

Bolt has a warm, welcoming, and helpful team of core developers who can, collectively,
help you to get a new feature into Bolt.

New features should have a "sponsor" on the core-team. You are welcome to, ask a
specific team member to sponsor your work (ask around on Slack or IRC), or we'll try
to match someone to your feature based on their area of expertise.

We have some simple processes to assist in getting new features from the concept stage,
right though to a released and maintained Bolt.

 1. Open an RFC [issue](https://github.com/bolt/bolt/issues/new) following these criteria:
     * Prefix the title with "[RFC]"
     * Breifely describe the use case
     * If requireing new libraries, a brief justification of why that one was chosen
 3. Be assigned a "sponsor" from the core team
 4. Submit the PR
 5. Celebrate with your new friends in the Bolt community!

New features should also be accompanied with:
   * Unit and/or acceptance tests
   * Documentation

Your sponsor is there to help you, as is a large and helpful community of people
on both Slack and IRC, should you have troubles with any of this.

**NOTE:** Sponsors help out in their own personal time, so please be patient and respectful
of their available time. We will do our best to do the same with you.

### Further reading

 - [Creating a pull request](https://help.github.com/articles/creating-a-pull-request/)
 - [Pull Request Tutorial](http://yangsu.github.io/pull-request-tutorial/)

Thank you for your contributions!

And finally, **one last tip**: Mention that you do not like
[brown M&M's](http://www.snopes.com/music/artists/vanhalen.asp). If you do,
we'll know you've read this page. ;-)
