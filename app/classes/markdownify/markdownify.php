<?php
/**
 * Markdownify converts HTML Markup to [Markdown][1] (by [John Gruber][2]. It
 * also supports [Markdown Extra][3] by [Michel Fortin][4] via Markdownify_Extra.
 *
 * It all started as `html2text.php` - a port of [Aaron Swartz'][5] [`html2text.py`][6] - but
 * got a long way since. This is far more than a mere port now!
 * Starting with version 2.0.0 this is a complete rewrite and cannot be
 * compared to Aaron Swatz' `html2text.py` anylonger. I'm now using a HTML parser
 * (see `parsehtml.php` which I also wrote) which makes most of the evil
 * RegEx magic go away and additionally it gives a much cleaner class
 * structure. Also notably is the fact that I now try to prevent regressions by
 * utilizing testcases of Michel Fortin's [MDTest][7].
 *
 * [1]: http://daringfireball.com/projects/markdown
 * [2]: http://daringfireball.com/
 * [3]: http://www.michelf.com/projects/php-markdown/extra/
 * [4]: http://www.michelf.com/
 * [5]: http://www.aaronsw.com/
 * [6]: http://www.aaronsw.com/2002/html2text/
 * [7]: http://article.gmane.org/gmane.text.markdown.general/2540
 *
 * @version 2.0.0 alpha
 * @author Milian Wolff (<mail@milianw.de>, <http://milianw.de>)
 * @license LGPL, see LICENSE_LGPL.txt and the summary below
 * @copyright (C) 2007  Milian Wolff
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * HTML Parser, see http://sf.net/projects/parseHTML
 */
require_once dirname(__FILE__).'/parsehtml.php';

/**
 * default configuration
 */
define('MDFY_LINKS_EACH_PARAGRAPH', false);
define('MDFY_BODYWIDTH', false);
define('MDFY_KEEPHTML', true);

/**
 * HTML to Markdown converter class
 */
class Markdownify {
  /**
   * html parser object
   *
   * @var parseHTML
   */
  var $parser;
  /**
   * markdown output
   *
   * @var string
   */
  var $output;
  /**
   * stack with tags which where not converted to html
   *
   * @var array<string>
   */
  var $notConverted = array();
  /**
   * skip conversion to markdown
   *
   * @var bool
   */
  var $skipConversion = false;
  /* options */
  /**
   * keep html tags which cannot be converted to markdown
   *
   * @var bool
   */
  var $keepHTML = false;
  /**
   * wrap output, set to 0 to skip wrapping
   *
   * @var int
   */
  var $bodyWidth = 0;
  /**
   * minimum body width
   *
   * @var int
   */
  var $minBodyWidth = 25;
  /**
   * display links after each paragraph
   *
   * @var bool
   */
  var $linksAfterEachParagraph = false;
  /**
   * constructor, set options, setup parser
   *
   * @param bool $linksAfterEachParagraph wether or not to flush stacked links after each paragraph
   *             defaults to false
   * @param int $bodyWidth wether or not to wrap the output to the given width
   *             defaults to false
   * @param bool $keepHTML wether to keep non markdownable HTML or to discard it
   *             defaults to true (HTML will be kept)
   * @return void
   */
  function Markdownify($linksAfterEachParagraph = MDFY_LINKS_EACH_PARAGRAPH, $bodyWidth = MDFY_BODYWIDTH, $keepHTML = MDFY_KEEPHTML) {
    $this->linksAfterEachParagraph = $linksAfterEachParagraph;
    $this->keepHTML = $keepHTML;

    if ($bodyWidth > $this->minBodyWidth) {
      $this->bodyWidth = intval($bodyWidth);
    } else {
      $this->bodyWidth = false;
    }

    $this->parser = new parseHTML;
    $this->parser->noTagsInCode = true;

    # we don't have to do this every time
    $search = array();
    $replace = array();
    foreach ($this->escapeInText as $s => $r) {
      array_push($search, '#(?<!\\\)'.$s.'#U');
      array_push($replace, $r);
    }
    $this->escapeInText = array(
      'search' => $search,
      'replace' => $replace
    );
  }
  /**
   * parse a HTML string
   *
   * @param string $html
   * @return string markdown formatted
   */
  function parseString($html) {
    $this->parser->html = $html;
    $this->parse();
    return $this->output;
  }
  /**
   * tags with elements which can be handled by markdown
   *
   * @var array<string>
   */
  var $isMarkdownable = array(
    'p' => array(),
    'ul' => array(),
    'ol' => array(),
    'li' => array(),
    'br' => array(),
    'blockquote' => array(),
    'code' => array(),
    'pre' => array(),
    'a' => array(
      'href' => 'required',
      'title' => 'optional',
    ),
    'strong' => array(),
    'b' => array(),
    'em' => array(),
    'i' => array(),
    'img' => array(
      'src' => 'required',
      'alt' => 'optional',
      'title' => 'optional',
    ),
    'h1' => array(),
    'h2' => array(),
    'h3' => array(),
    'h4' => array(),
    'h5' => array(),
    'h6' => array(),
    'hr' => array(),
  );
  /**
   * html tags to be ignored (contents will be parsed)
   *
   * @var array<string>
   */
  var $ignore = array(
    'html',
    'body',
  );
  /**
   * html tags to be dropped (contents will not be parsed!)
   *
   * @var array<string>
   */
  var $drop = array(
    'script',
    'head',
    'style',
    'form',
    'area',
    'object',
    'param',
    'iframe',
  );
  /**
   * Markdown indents which could be wrapped
   * @note: use strings in regex format
   *
   * @var array<string>
   */
  var $wrappableIndents = array(
    '\*   ', # ul
    '\d.  ', # ol
    '\d\d. ', # ol
    '> ', # blockquote
    '', # p
  );
  /**
   * list of chars which have to be escaped in normal text
   * @note: use strings in regex format
   *
   * @var array
   *
   * TODO: what's with block chars / sequences at the beginning of a block?
   */
  var $escapeInText = array(
    '([-*_])([ ]{0,2}\1){2,}' => '\\\\$0|', # hr
    '\*\*([^*\s]+)\*\*' => '\*\*$1\*\*', # strong
    '\*([^*\s]+)\*' => '\*$1\*', # em
    '__(?! |_)(.+)(?!<_| )__' => '\_\_$1\_\_', # em
    '_(?! |_)(.+)(?!<_| )_' => '\_$1\_', # em
    '`(.+)`' => '\`$1\`', # code
    '\[(.+)\](\s*\()' => '\[$1\]$2', # links: [text] (url) => [text\] (url)
    '\[(.+)\](\s*)\[(.*)\]' => '\[$1\]$2\[$3\]', # links: [text][id] => [text\][id\]
  );
  /**
   * wether last processed node was a block tag or not
   *
   * @var bool
   */
  var $lastWasBlockTag = false;
  /**
   * name of last closed tag
   *
   * @var string
   */
  var $lastClosedTag = '';
  /**
   * iterate through the nodes and decide what we
   * shall do with the current node
   *
   * @param void
   * @return void
   */
  function parse() {
    $this->output = '';
    # drop tags
    $this->parser->html = preg_replace('#<('.implode('|', $this->drop).')[^>]*>.*</\\1>#sU', '', $this->parser->html);
    while ($this->parser->nextNode()) {
      switch ($this->parser->nodeType) {
        case 'doctype':
          break;
        case 'pi':
        case 'comment':
          if ($this->keepHTML) {
            $this->flushLinebreaks();
            $this->out($this->parser->node);
            $this->setLineBreaks(2);
          }
          # else drop
          break;
        case 'text':
          $this->handleText();
          break;
        case 'tag':
          if (in_array($this->parser->tagName, $this->ignore)) {
            break;
          }
          if ($this->parser->isStartTag) {
            $this->flushLinebreaks();
          }
          if ($this->skipConversion) {
            $this->isMarkdownable(); # update notConverted
            $this->handleTagToText();
            continue;
          }
          if (!$this->parser->keepWhitespace && $this->parser->isBlockElement && $this->parser->isStartTag) {
            $this->parser->html = ltrim($this->parser->html);
          }
          if ($this->isMarkdownable()) {
            if ($this->parser->isBlockElement && $this->parser->isStartTag && !$this->lastWasBlockTag && !empty($this->output)) {
              if (!empty($this->buffer)) {
                $str =& $this->buffer[count($this->buffer) -1];
              } else {
                $str =& $this->output;
              }
              if (substr($str, -strlen($this->indent)-1) != "\n".$this->indent) {
                $str .= "\n".$this->indent;
              }
            }
            $func = 'handleTag_'.$this->parser->tagName;
            $this->$func();
            if ($this->linksAfterEachParagraph && $this->parser->isBlockElement && !$this->parser->isStartTag && empty($this->parser->openTags)) {
              $this->flushStacked();
            }
            if (!$this->parser->isStartTag) {
              $this->lastClosedTag = $this->parser->tagName;
            }
          } else {
            $this->handleTagToText();
            $this->lastClosedTag = '';
          }
          break;
        default:
          trigger_error('invalid node type', E_USER_ERROR);
          break;
      }
      $this->lastWasBlockTag = $this->parser->nodeType == 'tag' && $this->parser->isStartTag && $this->parser->isBlockElement;
    }
    if (!empty($this->buffer)) {
      trigger_error('buffer was not flushed, this is a bug. please report!', E_USER_WARNING);
      while (!empty($this->buffer)) {
        $this->out($this->unbuffer());
      }
    }
    ### cleanup
    $this->output = rtrim(str_replace('&amp;', '&', str_replace('&lt;', '<', str_replace('&gt;', '>', $this->output))));
    # end parsing, flush stacked tags
    $this->flushStacked();
    $this->stack = array();
  }
  /**
   * check if current tag can be converted to Markdown
   *
   * @param void
   * @return bool
   */
  function isMarkdownable() {
    if (!isset($this->isMarkdownable[$this->parser->tagName])) {
      # simply not markdownable
      return false;
    }
    if ($this->parser->isStartTag) {
      $return = true;
      if ($this->keepHTML) {
        $diff = array_diff(array_keys($this->parser->tagAttributes), array_keys($this->isMarkdownable[$this->parser->tagName]));
        if (!empty($diff)) {
          # non markdownable attributes given
          $return = false;
        }
      }
      if ($return) {
        foreach ($this->isMarkdownable[$this->parser->tagName] as $attr => $type) {
          if ($type == 'required' && !isset($this->parser->tagAttributes[$attr])) {
            # required markdown attribute not given
            $return = false;
            break;
          }
        }
      }
      if (!$return) {
        array_push($this->notConverted, $this->parser->tagName.'::'.implode('/', $this->parser->openTags));
      }
      return $return;
    } else {
      if (!empty($this->notConverted) && end($this->notConverted) === $this->parser->tagName.'::'.implode('/', $this->parser->openTags)) {
        array_pop($this->notConverted);
        return false;
      }
      return true;
    }
  }
  /**
   * output all stacked tags
   *
   * @param void
   * @return void
   */
  function flushStacked() {
    # links
    foreach ($this->stack as $tag => $a) {
      if (!empty($a)) {
        call_user_func(array(&$this, 'flushStacked_'.$tag));
      }
    }
  }
  /**
   * output link references (e.g. [1]: http://example.com "title");
   *
   * @param void
   * @return void
   */
  function flushStacked_a() {
    $out = false;
    foreach ($this->stack['a'] as $k => $tag) {
      if (!isset($tag['unstacked'])) {
        if (!$out) {
          $out = true;
          $this->out("\n\n", true);
        } else {
          $this->out("\n", true);
        }
        $this->out(' ['.$tag['linkID'].']: '.$tag['href'].(isset($tag['title']) ? ' "'.$tag['title'].'"' : ''), true);
        $tag['unstacked'] = true;
        $this->stack['a'][$k] = $tag;
      }
    }
  }
  /**
   * flush enqued linebreaks
   *
   * @param void
   * @return void
   */
  function flushLinebreaks() {
    if ($this->lineBreaks && !empty($this->output)) {
      $this->out(str_repeat("\n".$this->indent, $this->lineBreaks), true);
    }
    $this->lineBreaks = 0;
  }
  /**
   * handle non Markdownable tags
   *
   * @param void
   * @return void
   */
  function handleTagToText() {
    if (!$this->keepHTML) {
      if (!$this->parser->isStartTag && $this->parser->isBlockElement) {
        $this->setLineBreaks(2);
      }
    } else {
      # dont convert to markdown inside this tag
      /** TODO: markdown extra **/
      if (!$this->parser->isEmptyTag) {
        if ($this->parser->isStartTag) {
          if (!$this->skipConversion) {
            $this->skipConversion = $this->parser->tagName.'::'.implode('/', $this->parser->openTags);
          }
        } else {
          if ($this->skipConversion == $this->parser->tagName.'::'.implode('/', $this->parser->openTags)) {
            $this->skipConversion = false;
          }
        }
      }

      if ($this->parser->isBlockElement) {
        if ($this->parser->isStartTag) {
          if (in_array($this->parent(), array('ins', 'del'))) {
            # looks like ins or del are block elements now
            $this->out("\n", true);
            $this->indent('  ');
          }
          if ($this->parser->tagName != 'pre') {
            $this->out($this->parser->node."\n".$this->indent);
            if (!$this->parser->isEmptyTag) {
              $this->indent('  ');
            } else {
              $this->setLineBreaks(1);
            }
            $this->parser->html = ltrim($this->parser->html);
          } else {
            # don't indent inside <pre> tags
            $this->out($this->parser->node);
            static $indent;
            $indent =  $this->indent;
            $this->indent = '';
          }
        } else {
          if (!$this->parser->keepWhitespace) {
            $this->output = rtrim($this->output);
          }
          if ($this->parser->tagName != 'pre') {
            $this->indent('  ');
            $this->out("\n".$this->indent.$this->parser->node);
          } else {
            # reset indentation
            $this->out($this->parser->node);
            static $indent;
            $this->indent = $indent;
          }

          if (in_array($this->parent(), array('ins', 'del'))) {
            # ins or del was block element
            $this->out("\n");
            $this->indent('  ');
          }
          if ($this->parser->tagName == 'li') {
            $this->setLineBreaks(1);
          } else {
            $this->setLineBreaks(2);
          }
        }
      } else {
        $this->out($this->parser->node);
      }
      if (in_array($this->parser->tagName, array('code', 'pre'))) {
        if ($this->parser->isStartTag) {
          $this->buffer();
        } else {
          # add stuff so cleanup just reverses this
          $this->out(str_replace('&lt;', '&amp;lt;', str_replace('&gt;', '&amp;gt;', $this->unbuffer())));
        }
      }
    }
  }
  /**
   * handle plain text
   *
   * @param void
   * @return void
   */
  function handleText() {
    if ($this->hasParent('pre') && strpos($this->parser->node, "\n") !== false) {
      $this->parser->node = str_replace("\n", "\n".$this->indent, $this->parser->node);
    }
    if (!$this->hasParent('code') && !$this->hasParent('pre')) {
      # entity decode
      $this->parser->node = $this->decode($this->parser->node);
      if (!$this->skipConversion) {
        # escape some chars in normal Text
        $this->parser->node = preg_replace($this->escapeInText['search'], $this->escapeInText['replace'], $this->parser->node);
      }
    } else {
      $this->parser->node = str_replace(array('&quot;', '&apos'), array('"', '\''), $this->parser->node);
    }
    $this->out($this->parser->node);
    $this->lastClosedTag = '';
  }
  /**
   * handle <em> and <i> tags
   *
   * @param void
   * @return void
   */
  function handleTag_em() {
    $this->out('*', true);
  }
  function handleTag_i() {
    $this->handleTag_em();
  }
  /**
   * handle <strong> and <b> tags
   *
   * @param void
   * @return void
   */
  function handleTag_strong() {
    $this->out('**', true);
  }
  function handleTag_b() {
    $this->handleTag_strong();
  }
  /**
   * handle <h1> tags
   *
   * @param void
   * @return void
   */
  function handleTag_h1() {
    $this->handleHeader(1);
  }
  /**
   * handle <h2> tags
   *
   * @param void
   * @return void
   */
  function handleTag_h2() {
    $this->handleHeader(2);
  }
  /**
   * handle <h3> tags
   *
   * @param void
   * @return void
   */
  function handleTag_h3() {
    $this->handleHeader(3);
  }
  /**
   * handle <h4> tags
   *
   * @param void
   * @return void
   */
  function handleTag_h4() {
    $this->handleHeader(4);
  }
  /**
   * handle <h5> tags
   *
   * @param void
   * @return void
   */
  function handleTag_h5() {
    $this->handleHeader(5);
  }
  /**
   * handle <h6> tags
   *
   * @param void
   * @return void
   */
  function handleTag_h6() {
    $this->handleHeader(6);
  }
  /**
   * number of line breaks before next inline output
   */
  var $lineBreaks = 0;
  /**
   * handle header tags (<h1> - <h6>)
   *
   * @param int $level 1-6
   * @return void
   */
  function handleHeader($level) {
    if ($this->parser->isStartTag) {
      $this->out(str_repeat('#', $level).' ', true);
    } else {
      $this->setLineBreaks(2);
    }
  }
  /**
   * handle <p> tags
   *
   * @param void
   * @return void
   */
  function handleTag_p() {
    if (!$this->parser->isStartTag) {
      $this->setLineBreaks(2);
    }
  }
  /**
   * handle <a> tags
   *
   * @param void
   * @return void
   */
  function handleTag_a() {
    if ($this->parser->isStartTag) {
      $this->buffer();
      if (isset($this->parser->tagAttributes['title'])) {
        $this->parser->tagAttributes['title'] = $this->decode($this->parser->tagAttributes['title']);
      } else {
        $this->parser->tagAttributes['title'] = null;
      }
      $this->parser->tagAttributes['href'] = $this->decode(trim($this->parser->tagAttributes['href']));
      $this->stack();
    } else {
      $tag = $this->unstack();
      $buffer = $this->unbuffer();

      if (empty($tag['href']) && empty($tag['title'])) {
        # empty links... testcase mania, who would possibly do anything like that?!
        $this->out('['.$buffer.']()', true);
        return;
      }

      if ($buffer == $tag['href'] && empty($tag['title'])) {
        # <http://example.com>
        $this->out('<'.$buffer.'>', true);
        return;
      }

      $bufferDecoded = $this->decode(trim($buffer));
      if (substr($tag['href'], 0, 7) == 'mailto:' && 'mailto:'.$bufferDecoded == $tag['href']) {
        if (is_null($tag['title'])) {
          # <mail@example.com>
          $this->out('<'.$bufferDecoded.'>', true);
          return;
        }
        # [mail@example.com][1]
        # ...
        #  [1]: mailto:mail@example.com Title
        $tag['href'] = 'mailto:'.$bufferDecoded;
      }
      # [This link][id]
      foreach ($this->stack['a'] as $tag2) {
        if ($tag2['href'] == $tag['href'] && $tag2['title'] === $tag['title']) {
          $tag['linkID'] = $tag2['linkID'];
          break;
        }
      }
      if (!isset($tag['linkID'])) {
        $tag['linkID'] = count($this->stack['a']) + 1;
        array_push($this->stack['a'], $tag);
      }

      $this->out('['.$buffer.']['.$tag['linkID'].']', true);
    }
  }
  /**
   * handle <img /> tags
   *
   * @param void
   * @return void
   */
  function handleTag_img() {
    if (!$this->parser->isStartTag) {
      return; # just to be sure this is really an empty tag...
    }

    if (isset($this->parser->tagAttributes['title'])) {
      $this->parser->tagAttributes['title'] = $this->decode($this->parser->tagAttributes['title']);
    } else {
      $this->parser->tagAttributes['title'] = null;
    }
    if (isset($this->parser->tagAttributes['alt'])) {
      $this->parser->tagAttributes['alt'] = $this->decode($this->parser->tagAttributes['alt']);
    } else {
      $this->parser->tagAttributes['alt'] = null;
    }

    if (empty($this->parser->tagAttributes['src'])) {
      # support for "empty" images... dunno if this is really needed
      # but there are some testcases which do that...
      if (!empty($this->parser->tagAttributes['title'])) {
        $this->parser->tagAttributes['title'] = ' '.$this->parser->tagAttributes['title'].' ';
      }
      $this->out('!['.$this->parser->tagAttributes['alt'].']('.$this->parser->tagAttributes['title'].')', true);
      return;
    } else {
      $this->parser->tagAttributes['src'] = $this->decode($this->parser->tagAttributes['src']);
    }

    # [This link][id]
    $link_id = false;
    if (!empty($this->stack['a'])) {
      foreach ($this->stack['a'] as $tag) {
        if ($tag['href'] == $this->parser->tagAttributes['src']
            && $tag['title'] === $this->parser->tagAttributes['title']) {
          $link_id = $tag['linkID'];
          break;
        }
      }
    } else {
      $this->stack['a'] = array();
    }
    if (!$link_id) {
      $link_id = count($this->stack['a']) + 1;
      $tag = array(
        'href' => $this->parser->tagAttributes['src'],
        'linkID' => $link_id,
        'title' => $this->parser->tagAttributes['title']
      );
      array_push($this->stack['a'], $tag);
    }

    $this->out('!['.$this->parser->tagAttributes['alt'].']['.$link_id.']', true);
  }
  /**
   * handle <code> tags
   *
   * @param void
   * @return void
   */
  function handleTag_code() {
    if ($this->hasParent('pre')) {
      # ignore code blocks inside <pre>
      return;
    }
    if ($this->parser->isStartTag) {
      $this->buffer();
    } else {
      $buffer = $this->unbuffer();
      # use as many backticks as needed
      preg_match_all('#`+#', $buffer, $matches);
      if (!empty($matches[0])) {
        rsort($matches[0]);

        $ticks = '`';
        while (true) {
          if (!in_array($ticks, $matches[0])) {
            break;
          }
          $ticks .= '`';
        }
      } else {
        $ticks = '`';
      }
      if ($buffer[0] == '`' || substr($buffer, -1) == '`') {
        $buffer = ' '.$buffer.' ';
      }
      $this->out($ticks.$buffer.$ticks, true);
    }
  }
  /**
   * handle <pre> tags
   *
   * @param void
   * @return void
   */
  function handleTag_pre() {
    if ($this->keepHTML && $this->parser->isStartTag) {
      # check if a simple <code> follows
      if (!preg_match('#^\s*<code\s*>#Us', $this->parser->html)) {
        # this is no standard markdown code block
        $this->handleTagToText();
        return;
      }
    }
    $this->indent('    ');
    if (!$this->parser->isStartTag) {
      $this->setLineBreaks(2);
    } else {
      $this->parser->html = ltrim($this->parser->html);
    }
  }
  /**
   * handle <blockquote> tags
   *
   * @param void
   * @return void
   */
  function handleTag_blockquote() {
    $this->indent('> ');
  }
  /**
   * handle <ul> tags
   *
   * @param void
   * @return void
   */
  function handleTag_ul() {
    if ($this->parser->isStartTag) {
      $this->stack();
      if (!$this->keepHTML && $this->lastClosedTag == $this->parser->tagName) {
        $this->out("\n".$this->indent.'<!-- -->'."\n".$this->indent."\n".$this->indent);
      }
    } else {
      $this->unstack();
      if ($this->parent() != 'li' || preg_match('#^\s*(</li\s*>\s*<li\s*>\s*)?<(p|blockquote)\s*>#sU', $this->parser->html)) {
        # dont make Markdown add unneeded paragraphs
        $this->setLineBreaks(2);
      }
    }
  }
  /**
   * handle <ul> tags
   *
   * @param void
   * @return void
   */
  function handleTag_ol() {
    # same as above
    $this->parser->tagAttributes['num'] = 0;
    $this->handleTag_ul();
  }
  /**
   * handle <li> tags
   *
   * @param void
   * @return void
   */
  function handleTag_li() {
    if ($this->parent() == 'ol') {
      $parent =& $this->getStacked('ol');
      if ($this->parser->isStartTag) {
        $parent['num']++;
        $this->out($parent['num'].'.'.str_repeat(' ', 3 - strlen($parent['num'])), true);
      }
      $this->indent('    ', false);
    } else {
      if ($this->parser->isStartTag) {
        $this->out('*   ', true);
      }
      $this->indent('    ', false);
    }
    if (!$this->parser->isStartTag) {
      $this->setLineBreaks(1);
    }
  }
  /**
   * handle <hr /> tags
   *
   * @param void
   * @return void
   */
  function handleTag_hr() {
    if (!$this->parser->isStartTag) {
      return; # just to be sure this really is an empty tag
    }
    $this->out('* * *', true);
    $this->setLineBreaks(2);
  }
  /**
   * handle <br /> tags
   *
   * @param void
   * @return void
   */
  function handleTag_br() {
    $this->out("  \n".$this->indent, true);
    $this->parser->html = ltrim($this->parser->html);
  }
  /**
   * node stack, e.g. for <a> and <abbr> tags
   *
   * @var array<array>
   */
  var $stack = array();
  /**
   * add current node to the stack
   * this only stores the attributes
   *
   * @param void
   * @return void
   */
  function stack() {
    if (!isset($this->stack[$this->parser->tagName])) {
      $this->stack[$this->parser->tagName] = array();
    }
    array_push($this->stack[$this->parser->tagName], $this->parser->tagAttributes);
  }
  /**
   * remove current tag from stack
   *
   * @param void
   * @return array
   */
  function unstack() {
    if (!isset($this->stack[$this->parser->tagName]) || !is_array($this->stack[$this->parser->tagName])) {
      trigger_error('Trying to unstack from empty stack. This must not happen.', E_USER_ERROR);
    }
    return array_pop($this->stack[$this->parser->tagName]);
  }
  /**
   * get last stacked element of type $tagName
   *
   * @param string $tagName
   * @return array
   */
  function & getStacked($tagName) {
    // no end() so it can be referenced
    return $this->stack[$tagName][count($this->stack[$tagName])-1];
  }
  /**
   * set number of line breaks before next start tag
   *
   * @param int $number
   * @return void
   */
  function setLineBreaks($number) {
    if ($this->lineBreaks < $number) {
      $this->lineBreaks = $number;
    }
  }
  /**
   * stores current buffers
   *
   * @var array<string>
   */
  var $buffer = array();
  /**
   * buffer next parser output until unbuffer() is called
   *
   * @param void
   * @return void
   */
  function buffer() {
    array_push($this->buffer, '');
  }
  /**
   * end current buffer and return buffered output
   *
   * @param void
   * @return string
   */
  function unbuffer() {
    return array_pop($this->buffer);
  }
  /**
   * append string to the correct var, either
   * directly to $this->output or to the current
   * buffers
   *
   * @param string $put
   * @return void
   */
  function out($put, $nowrap = false) {
    if (empty($put)) {
      return;
    }
    if (!empty($this->buffer)) {
      $this->buffer[count($this->buffer) - 1] .= $put;
    } else {
      if ($this->bodyWidth && !$this->parser->keepWhitespace) { # wrap lines
        // get last line
        $pos = strrpos($this->output, "\n");
        if ($pos === false) {
          $line = $this->output;
        } else {
          $line = substr($this->output, $pos);
        }

        if ($nowrap) {
          if ($put[0] != "\n" && $this->strlen($line) + $this->strlen($put) > $this->bodyWidth) {
            $this->output .= "\n".$this->indent.$put;
          } else {
            $this->output .= $put;
          }
          return;
        } else {
          $put .= "\n"; # make sure we get all lines in the while below
          $lineLen = $this->strlen($line);
          while ($pos = strpos($put, "\n")) {
            $putLine = substr($put, 0, $pos+1);
            $put = substr($put, $pos+1);
            $putLen = $this->strlen($putLine);
            if ($lineLen + $putLen < $this->bodyWidth) {
              $this->output .= $putLine;
              $lineLen = $putLen;
            } else {
              $split = preg_split('#^(.{0,'.($this->bodyWidth - $lineLen).'})\b#', $putLine, 2, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_DELIM_CAPTURE);
              $this->output .= rtrim($split[1][0])."\n".$this->indent.$this->wordwrap(ltrim($split[2][0]), $this->bodyWidth, "\n".$this->indent, false);
            }
          }
          $this->output = substr($this->output, 0, -1);
          return;
        }
      } else {
        $this->output .= $put;
      }
    }
  }
  /**
   * current indentation
   *
   * @var string
   */
  var $indent = '';
  /**
   * indent next output (start tag) or unindent (end tag)
   *
   * @param string $str indentation
   * @param bool $output add indendation to output
   * @return void
   */
  function indent($str, $output = true) {
    if ($this->parser->isStartTag) {
      $this->indent .= $str;
      if ($output) {
        $this->out($str, true);
      }
    } else {
      $this->indent = substr($this->indent, 0, -strlen($str));
    }
  }
  /**
   * decode email addresses
   *
   * @author derernst@gmx.ch <http://www.php.net/manual/en/function.html-entity-decode.php#68536>
   * @author Milian Wolff <http://milianw.de>
   */
  function decode($text, $quote_style = ENT_QUOTES) {
    if (version_compare(PHP_VERSION, '5', '>=')) {
      # UTF-8 is only supported in PHP 5.x.x and above
      $text = html_entity_decode($text, $quote_style, 'UTF-8');
    } else {
      if (function_exists('html_entity_decode')) {
        $text = html_entity_decode($text, $quote_style, 'ISO-8859-1');
      } else {
        static $trans_tbl;
        if (!isset($trans_tbl)) {
          $trans_tbl = array_flip(get_html_translation_table(HTML_ENTITIES, $quote_style));
        }
        $text = strtr($text, $trans_tbl);
      }
      $text = preg_replace_callback('~&#x([0-9a-f]+);~i', array(&$this, '_decode_hex'), $text);
      $text = preg_replace_callback('~&#(\d{2,5});~', array(&$this, '_decode_numeric'), $text);
    }
    return $text;
  }
  /**
   * callback for decode() which converts a hexadecimal entity to UTF-8
   *
   * @param array $matches
   * @return string UTF-8 encoded
   */
  function _decode_hex($matches) {
    return $this->unichr(hexdec($matches[1]));
  }
  /**
   * callback for decode() which converts a numerical entity to UTF-8
   *
   * @param array $matches
   * @return string UTF-8 encoded
   */
  function _decode_numeric($matches) {
    return $this->unichr($matches[1]);
  }
  /**
   * UTF-8 chr() which supports numeric entities
   *
   * @author grey - greywyvern - com <http://www.php.net/manual/en/function.chr.php#55978>
   * @param array $matches
   * @return string UTF-8 encoded
   */
  function unichr($dec) {
    if ($dec < 128) {
      $utf = chr($dec);
    } else if ($dec < 2048) {
      $utf = chr(192 + (($dec - ($dec % 64)) / 64));
      $utf .= chr(128 + ($dec % 64));
    } else {
      $utf = chr(224 + (($dec - ($dec % 4096)) / 4096));
      $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
      $utf .= chr(128 + ($dec % 64));
    }
    return $utf;
  }
  /**
   * UTF-8 strlen()
   *
   * @param string $str
   * @return int
   *
   * @author dtorop 932 at hotmail dot com <http://www.php.net/manual/en/function.strlen.php#37975>
   * @author Milian Wolff <http://milianw.de>
   */
  function strlen($str) {
    if (function_exists('mb_strlen')) {
      return mb_strlen($str, 'UTF-8');
    } else {
      return preg_match_all('/[\x00-\x7F\xC0-\xFD]/', $str, $var_empty);
    }
  }
  /**
  * wordwrap for utf8 encoded strings
  *
  * @param string $str
  * @param integer $len
  * @param string $what
  * @return string
  */
  function wordwrap($str, $width, $break, $cut = false){
    if (!$cut) {
      $regexp = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){1,'.$width.'}\b#';
    } else {
      $regexp = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){'.$width.'}#';
    }
    $return = '';
    while (preg_match($regexp, $str, $matches)) {
      $string = $matches[0];
      $str = ltrim(substr($str, strlen($string)));
      if (!$cut && isset($str[0]) && in_array($str[0], array('.', '!', ';', ':', '?', ','))) {
        $string .= $str[0];
        $str = ltrim(substr($str, 1));
      }
      $return .= $string.$break;
    }
    return $return.ltrim($str);
  }
  /**
   * check if current node has a $tagName as parent (somewhere, not only the direct parent)
   *
   * @param string $tagName
   * @return bool
   */
  function hasParent($tagName) {
    return in_array($tagName, $this->parser->openTags);
  }
  /**
   * get tagName of direct parent tag
   *
   * @param void
   * @return string $tagName
   */
  function parent() {
    return end($this->parser->openTags);
  }
}