Contributing to Bolt
====================

If you are reading this, you must be considering opening an issue for help with
a bug, some trouble you're having, or to suggest a new feature. Great!

To streamline this process, we've made this small document, to help you report
your issue or feature more efficiently. We did out best to keep it brief, so
please read it carefully.

**Note:** Please don't use the our main GitHub repository for reporting bugs
with _extensions_. If you have found a bug in an extension, the best place to
report this would be in the extension's own repository, so that the original
author can look into it.


I'm reporting a bug
-------------------

If you think you've found a bug, try to make sure it's actually a bug, and not the result of something else going wrong. For example, make sure your webserver is functioning correctly, and try the issue in another browser, if possible. Be sure to **search for similar bugs**. Perhaps somebody has already reported the issue. If so, you should add any additional informtion to the existing bug. It might make it easier for us to fix, and at the very least it'll get bumped higher onto the list.

After you've done these things, and you are fairly certain you've found a news bug, please open an issue to report it. Create a [GitHub account](https://github.com), if you don't have one yet.

When posting your bug, please include the following:

 - **Bug summary**: Write a short summary of the bug. Try to pinpoint it as much a possible. Try to state the _actual problem_, and not just what you _think_ the solution might be.
 - **Specifics**: Mention the URL where this bug occurs, if applicable. What version of Bolt are you using (down to the very last digit!), and what method did you use to install it? What browser and version are you using? Please mention if you've checked it in other browsers as well.
 - **Steps to reproduce**: Clearly mention the steps to reproduce the bug.
 - **Expected result**: What did you _expect_ that would happen on your Bolt site? Describe the intended outcome after you did the steps mentioned before.
 - **Actual result**: What is the actual result of the above steps? So, describe the behavior of the bug. Please, please inlcude **Error messages** and screenshots. They might mean nothing to you, but they are _very_ helpful to us.

Further reading: [10 tips for better Pull Requests](http://blog.ploeh.dk/2015/01/15/10-tips-for-better-pull-requests/)


#### Reporting security issues

If you wish to contact us privately about any possible security issues in Bolt,
please contact us at [security@bolt.cm](mailto:security@bolt.cm). Your email
will be handled confidentially. After fixing them, you will be credited for any
security issues that you may discover.


I'd like to request a feature
-----------------------------


Use Github if you are planning on contributing a new feature and developing it. If you want to discuss your idea first, before "officially" posting it anywhere, you can always join us on [IRC](http://bolt.cm/community).


I'm making a pull request
-------------------------

Your contributions to the project are very welcome. If you would like to fix a bug or propose a new feature, you can submit a Pull Request.

To help us merge your Pull Request, please make sure you follow these points:


1. Describe the problem clearly in the Pull Request description
2. Please refer to a bug report on the issue list. An example of a good title is "Fixes problem [x] by doing [y]. Fixes #1555"
3. Do not edit compiled asset files such as `bolt.min.js` or `bolt.css`. Instead, try to edit the Sass files inside the `sass/` directory. We'll handle the updated compiled files.
4. For any change that you make, **please try to also add a test case(s)** in the `tests` directory. This helps us understand the issue and make sure that it will stay fixed forever.



Further reading:
 - [Creating a pull request](https://help.github.com/articles/creating-a-pull-request/)
 - [Pull Request Tutorial](http://yangsu.github.io/pull-request-tutorial/)



Thank you for your contributions!


And finally, **one last tip**: Mention that you do not like blue M&M's. If you do, we'll know you've read this page. ;-)


