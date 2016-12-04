[Prefix your PR title with [WIP], if it's a 'work in progress', 
or if you're looking for feedback before merging.]

A brief description of the Pull Request goes here. If you haven't yet done so,
please read the 'contributing guidelines' thoroughly. 

See: https://github.com/bolt/bolt/blob/master/.github/CONTRIBUTING.md

Fixes: #1555 (please refer to an existing issue number, that this PR fixes)


Details
-------

Please list any details for the PR, that might be relevant. Some pointers:

 - Please include tests with your PR. If you do, we will love you for it.
 - Check if the change in the PR is a good fit for Bolt, or if it would be
   better off as an extension.
   
Choosing a target branch
------------------------

Bolt has a branching strategy that follow these rules: 

 * `release/3.X` — "stable" branch (Note: `X` will be a number)
 * `release/3.Y` — "beta" branch (Note: `Y` will be a number, one greater than `X`)
 * `3.x` — "alpha" branch, major features should be sent here (Note: `x` is literal, it really is an "x")
 * `master` — 4.x development. Things will break, and if they do you get to keep both pieces!
