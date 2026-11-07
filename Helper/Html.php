<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/ (BSD-3-Clause)
 *
 * Modified for Magento 2
 * @copyright Copyright (c) 2019 Volodymyr Hryvinskyi. All rights reserved.
 * @author Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 * @github <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\Base\Helper;

class Html
{
    /**
     * Regular expression used for attribute name validation.
     *
     * @var string
     */
    public static $attributeRegex = '/(^|.*\])([\w\.\+]+)(\[.*|$)/u';

    /**
     * Void elements (element name => 1): they are rendered self-closed, e.g. `<br />`.
     *
     * @var array<string, int>
     * @see http://www.w3.org/TR/html-markup/syntax.html#void-element
     */
    public static $voidElements = [
        'area' => 1,
        'base' => 1,
        'br' => 1,
        'col' => 1,
        'command' => 1,
        'embed' => 1,
        'hr' => 1,
        'img' => 1,
        'input' => 1,
        'keygen' => 1,
        'link' => 1,
        'meta' => 1,
        'param' => 1,
        'source' => 1,
        'track' => 1,
        'wbr' => 1,
    ];

    /**
     * The preferred order of attributes in a tag, as rendered by renderTagAttributes().
     *
     * @var list<string>
     */
    public static $attributeOrder = [
        'type',
        'id',
        'class',
        'name',
        'value',

        'href',
        'src',
        'srcset',
        'form',
        'action',
        'method',

        'selected',
        'checked',
        'readonly',
        'disabled',
        'multiple',

        'size',
        'maxlength',
        'width',
        'height',
        'rows',
        'cols',

        'alt',
        'title',
        'rel',
        'media',
    ];

    /**
     * Tag attributes that are expanded when their value is an array.
     *
     * If the value of the `data` attribute is `['name' => 'xyz', 'age' => 13]`, two attributes
     * are generated instead of one: `data-name="xyz" data-age="13"`.
     *
     * @var list<string>
     */
    public static $dataAttributes = ['aria', 'data', 'data-ng', 'ng'];

    /**
     * Whether duplicate CSS classes are removed when classes are rendered or merged.
     *
     * @var bool
     */
    public static $normalizeClassAttribute = true;

    /**
     * Encodes special characters into HTML entities, using the UTF-8 charset.
     *
     * @param string|int|float|bool|\Stringable|null $content the content to be encoded
     * @param bool $doubleEncode whether to encode HTML entities in `$content`. If false,
     * HTML entities in `$content` will not be further encoded.
     * @return string the encoded content
     * @see decode()
     * @see http://www.php.net/manual/en/function.htmlspecialchars.php
     */
    public static function encode($content, $doubleEncode = true)
    {
        return htmlspecialchars((string)$content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }

    /**
     * Decodes special HTML entities back to the corresponding characters.
     *
     * This is the opposite of encode().
     *
     * @param string $content the content to be decoded
     * @return string the decoded content
     * @see encode()
     * @see http://www.php.net/manual/en/function.htmlspecialchars-decode.php
     */
    public static function decode($content)
    {
        return htmlspecialchars_decode($content, ENT_QUOTES);
    }

    /**
     * Generates a complete HTML tag; a void element is rendered self-closed, e.g. `<br />`.
     *
     * @param string|bool|null $name the tag name. If $name is `null` or `false`, the content is returned
     * without any tag.
     * @param string $content the content to be enclosed between the start and end tags. It will not be
     * HTML-encoded. If this is coming from end users, consider encode() it to prevent XSS attacks.
     * @param array<mixed> $options the HTML tag attributes (HTML options) in terms of name-value pairs.
     * These will be rendered as the attributes of the resulting tag. The values will be HTML-encoded using
     * encode(). If a value is null, the corresponding attribute will not be rendered.
     *
     * For example when using `['class' => 'my-class', 'target' => '_blank', 'value' => null]` it will result
     * in the html attributes rendered like this: `class="my-class" target="_blank"`.
     *
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated HTML tag
     * @see beginTag()
     * @see endTag()
     */
    public static function tag($name, $content = '', $options = [])
    {
        if ($name === null || $name === false) {
            return $content;
        }
        $html = "<$name" . static::renderTagAttributes($options);
        return isset(static::$voidElements[strtolower((string)$name)]) ? "$html />" : "$html>$content</$name>";
    }

    /**
     * Generates a start tag.
     *
     * @param string|bool|null $name the tag name. If $name is `null` or `false`, an empty string is returned.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated start tag
     * @see endTag()
     * @see tag()
     */
    public static function beginTag($name, $options = [])
    {
        if ($name === null || $name === false) {
            return '';
        }

        return "<$name" . static::renderTagAttributes($options) . '>';
    }

    /**
     * Generates an end tag.
     *
     * @param string|bool|null $name the tag name. If $name is `null` or `false`, an empty string is returned.
     * @return string the generated end tag
     * @see beginTag()
     * @see tag()
     */
    public static function endTag($name)
    {
        if ($name === null || $name === false) {
            return '';
        }

        return "</$name>";
    }

    /**
     * Generates a style tag.
     *
     * @param string $content the style content
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated style tag
     */
    public static function style($content, $options = [])
    {
        return static::tag('style', $content, $options);
    }

    /**
     * Generates a script tag.
     *
     * @param string $content the script content
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated script tag
     */
    public static function script($content, $options = [])
    {
        return static::tag('script', $content, $options);
    }

    /**
     * Generates a link tag that refers to an external CSS file.
     *
     * @param string $url the URL of the external CSS file, printed as given (HTML-encoded).
     * @param array<mixed> $options the tag options in terms of name-value pairs. The following options are
     * specially handled:
     *
     * - condition: specifies the conditional comments for IE, e.g., `lt IE 9`. When this is specified,
     *   the generated `link` tag will be enclosed within the conditional comments. This is mainly useful
     *   for supporting old versions of IE browsers.
     * - noscript: if set to true, `link` tag will be wrapped into `<noscript>` tags.
     *
     * The rest of the options will be rendered as the attributes of the resulting link tag. The values will
     * be HTML-encoded using encode(). If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated link tag
     */
    public static function cssFile($url, $options = [])
    {
        if (!isset($options['rel'])) {
            $options['rel'] = 'stylesheet';
        }
        $options['href'] = $url;
        if (isset($options['condition'])) {
            $condition = self::toText($options['condition']);
            unset($options['condition']);
            return self::wrapIntoCondition(static::tag('link', '', $options), $condition);
        } elseif (isset($options['noscript']) && $options['noscript'] === true) {
            unset($options['noscript']);
            return '<noscript>' . static::tag('link', '', $options) . '</noscript>';
        }

        return static::tag('link', '', $options);
    }

    /**
     * Generates a script tag that refers to an external JavaScript file.
     *
     * @param string $url the URL of the external JavaScript file, printed as given (HTML-encoded).
     * @param array<mixed> $options the tag options in terms of name-value pairs. The following option is
     * specially handled:
     *
     * - condition: specifies the conditional comments for IE, e.g., `lt IE 9`. When this is specified,
     *   the generated `script` tag will be enclosed within the conditional comments. This is mainly useful
     *   for supporting old versions of IE browsers.
     *
     * The rest of the options will be rendered as the attributes of the resulting script tag. The values will
     * be HTML-encoded using encode(). If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated script tag
     */
    public static function jsFile($url, $options = [])
    {
        $options['src'] = $url;
        if (isset($options['condition'])) {
            $condition = self::toText($options['condition']);
            unset($options['condition']);
            return self::wrapIntoCondition(static::tag('script', '', $options), $condition);
        }

        return static::tag('script', '', $options);
    }

    /**
     * Wraps given content into conditional comments for IE, e.g., `lt IE 9`.
     *
     * @param string $content raw HTML content.
     * @param string $condition condition string.
     * @return string generated HTML.
     */
    private static function wrapIntoCondition(string $content, string $condition): string
    {
        if (strpos($condition, '!IE') !== false) {
            return "<!--[if $condition]><!-->\n" . $content . "\n<!--<![endif]-->";
        }

        return "<!--[if $condition]>\n" . $content . "\n<![endif]-->";
    }

    /**
     * Generates a form start tag.
     *
     * @param string $action the form action URL, printed as given. For the "get" method its query parameters
     * become hidden inputs, because a browser drops the query string of a GET form's action.
     * @param string $method the form submission method, printed as given.
     * @param string|null $formKey the Magento form key; when given, a hidden "form_key" input carries it.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated form start tag.
     * @see endForm()
     */
    public static function beginForm($action = '', $method = 'post', $formKey = null, $options = [])
    {
        $hiddenInputs = [];
        if ($formKey !== null) {
            $hiddenInputs[] = static::hiddenInput('form_key', $formKey);
        }
        if (!strcasecmp($method, 'get') && ($pos = strpos($action, '?')) !== false) {
            // query parameters in the action are ignored for GET method
            // we use hidden fields to add them back
            foreach (explode('&', substr($action, $pos + 1)) as $pair) {
                if (($pos1 = strpos($pair, '=')) !== false) {
                    $hiddenInputs[] = static::hiddenInput(
                        urldecode(substr($pair, 0, $pos1)),
                        urldecode(substr($pair, $pos1 + 1))
                    );
                } else {
                    $hiddenInputs[] = static::hiddenInput(urldecode($pair), '');
                }
            }
            $action = substr($action, 0, $pos);
        }
        $options['action'] = $action;
        $options['method'] = $method;
        $form = static::beginTag('form', $options);
        if (!empty($hiddenInputs)) {
            $form .= "\n" . implode("\n", $hiddenInputs);
        }
        return $form;
    }

    /**
     * Generates a form end tag.
     *
     * @return string the generated tag
     * @see beginForm()
     */
    public static function endForm()
    {
        return '</form>';
    }

    /**
     * Generates a hyperlink tag.
     *
     * @param string $text link body. It will NOT be HTML-encoded. Therefore you can pass in HTML code
     * such as an image tag. If this is coming from end users, you should consider encode()
     * it to prevent XSS attacks.
     * @param string|null $url the URL for the "href" attribute, printed as given (HTML-encoded). If it is null,
     * the "href" attribute is not generated.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated hyperlink
     */
    public static function a($text, $url = null, $options = [])
    {
        if ($url !== null) {
            $options['href'] = $url;
        }
        return static::tag('a', $text, $options);
    }

    /**
     * Generates a mailto hyperlink.
     *
     * @param string $text link body. It will NOT be HTML-encoded. Therefore you can pass in HTML code
     * such as an image tag. If this is coming from end users, you should consider encode()
     * it to prevent XSS attacks.
     * @param string|null $email email address. If this is null, the first parameter (link body) will be treated
     * as the email address and used.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated mailto link
     */
    public static function mailto($text, $email = null, $options = [])
    {
        $options['href'] = 'mailto:' . ($email === null ? $text : $email);
        return static::tag('a', $text, $options);
    }

    /**
     * Generates an image tag.
     *
     * @param string $src the image URL, printed as given (HTML-encoded).
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     *
     * The `srcset` option may be an array whose keys are descriptors and whose values are URLs; it is
     * rendered as a comma-separated list of "URL descriptor" pairs. An empty "alt" is added when none is given.
     * @return string the generated image tag.
     */
    public static function img($src, $options = [])
    {
        $options['src'] = $src;

        if (isset($options['srcset']) && is_array($options['srcset'])) {
            $srcset = [];
            foreach ($options['srcset'] as $descriptor => $url) {
                $srcset[] = self::toText($url) . ' ' . $descriptor;
            }
            $options['srcset'] = implode(',', $srcset);
        }

        if (!isset($options['alt'])) {
            $options['alt'] = '';
        }

        return static::tag('img', '', $options);
    }

    /**
     * Generates a label tag.
     *
     * @param string $content label text. It will NOT be HTML-encoded. Therefore you can pass in HTML code
     * such as an image tag. If this is is coming from end users, you should encode()
     * it to prevent XSS attacks.
     * @param string|null $for the ID of the HTML element that this label is associated with.
     * If this is null, the "for" attribute will not be generated.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated label tag
     */
    public static function label($content, $for = null, $options = [])
    {
        $options['for'] = $for;
        return static::tag('label', $content, $options);
    }

    /**
     * Generates a button tag.
     *
     * @param string $content the content enclosed within the button tag. It will NOT be HTML-encoded.
     * Therefore, you can pass in HTML code such as an image tag. If this is is coming from end users,
     * you should consider encode() it to prevent XSS attacks.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated button tag
     */
    public static function button($content = 'Button', $options = [])
    {
        if (!isset($options['type'])) {
            $options['type'] = 'button';
        }
        return static::tag('button', $content, $options);
    }

    /**
     * Generates a submit button tag.
     *
     * Be careful when naming form elements such as submit buttons. According to the
     * [jQuery documentation](https://api.jquery.com/submit/) there are some reserved names that can cause
     * conflicts, e.g. `submit`, `length`, or `method`.
     *
     * @param string $content the content enclosed within the button tag. It will NOT be HTML-encoded.
     * Therefore you can pass in HTML code such as an image tag. If this is is coming from end users,
     * you should consider encode() it to prevent XSS attacks.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated submit button tag
     */
    public static function submitButton($content = 'Submit', $options = [])
    {
        $options['type'] = 'submit';
        return static::button($content, $options);
    }

    /**
     * Generates a reset button tag.
     *
     * @param string $content the content enclosed within the button tag. It will NOT be HTML-encoded.
     * Therefore you can pass in HTML code such as an image tag. If this is is coming from end users,
     * you should consider encode() it to prevent XSS attacks.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated reset button tag
     */
    public static function resetButton($content = 'Reset', $options = [])
    {
        $options['type'] = 'reset';
        return static::button($content, $options);
    }

    /**
     * Generates an input type of the given type.
     *
     * @param string $type the type attribute.
     * @param string|null $name the name attribute. If it is null, the name attribute will not be generated.
     * @param string|int|float|bool|\Stringable|null $value the value attribute, cast to a string. If it is null,
     * the value attribute will not be generated.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated input tag
     */
    public static function input($type, $name = null, $value = null, $options = [])
    {
        if (!isset($options['type'])) {
            $options['type'] = $type;
        }
        $options['name'] = $name;
        $options['value'] = $value === null ? null : (string) $value;
        return static::tag('input', '', $options);
    }

    /**
     * Generates an input button.
     *
     * @param string|null $label the value attribute. If it is null, the value attribute will not be generated.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated button tag
     */
    public static function buttonInput($label = 'Button', $options = [])
    {
        $options['type'] = 'button';
        $options['value'] = $label;
        return static::tag('input', '', $options);
    }

    /**
     * Generates a submit input button.
     *
     * Be careful when naming form elements such as submit buttons. According to the
     * [jQuery documentation](https://api.jquery.com/submit/) there are some reserved names that can cause
     * conflicts, e.g. `submit`, `length`, or `method`.
     *
     * @param string|null $label the value attribute. If it is null, the value attribute will not be generated.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated button tag
     */
    public static function submitInput($label = 'Submit', $options = [])
    {
        $options['type'] = 'submit';
        $options['value'] = $label;
        return static::tag('input', '', $options);
    }

    /**
     * Generates a reset input button.
     *
     * @param string|null $label the value attribute. If it is null, the value attribute will not be generated.
     * @param array<mixed> $options the attributes of the button tag. The values will be HTML-encoded using
     * encode(). Attributes whose value is null will be ignored and not put in the tag returned.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated button tag
     */
    public static function resetInput($label = 'Reset', $options = [])
    {
        $options['type'] = 'reset';
        $options['value'] = $label;
        return static::tag('input', '', $options);
    }

    /**
     * Generates a text input field.
     *
     * @param string|null $name the name attribute.
     * @param string|int|float|bool|\Stringable|null $value the value attribute. If it is null, the value
     * attribute will not be generated.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated text input tag
     */
    public static function textInput($name, $value = null, $options = [])
    {
        return static::input('text', $name, $value, $options);
    }

    /**
     * Generates a hidden input field.
     *
     * @param string|null $name the name attribute.
     * @param string|int|float|bool|\Stringable|null $value the value attribute. If it is null, the value
     * attribute will not be generated.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated hidden input tag
     */
    public static function hiddenInput($name, $value = null, $options = [])
    {
        return static::input('hidden', $name, $value, $options);
    }

    /**
     * Generates a password input field.
     *
     * @param string|null $name the name attribute.
     * @param string|int|float|bool|\Stringable|null $value the value attribute. If it is null, the value
     * attribute will not be generated.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated password input tag
     */
    public static function passwordInput($name, $value = null, $options = [])
    {
        return static::input('password', $name, $value, $options);
    }

    /**
     * Generates a file input field.
     *
     * To use a file input field, you should set the enclosing form's "enctype" attribute to
     * be "multipart/form-data". After the form is submitted, the uploaded file information
     * can be obtained via $_FILES[$name] (see PHP documentation).
     *
     * @param string|null $name the name attribute.
     * @param string|int|float|bool|\Stringable|null $value the value attribute. If it is null, the value
     * attribute will not be generated.
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated file input tag
     */
    public static function fileInput($name, $value = null, $options = [])
    {
        return static::input('file', $name, $value, $options);
    }

    /**
     * Generates a text area input.
     *
     * @param string|null $name the input name
     * @param string|int|float|bool|\Stringable|null $value the input value. Note that it will be encoded
     * using encode().
     * @param array<mixed> $options the tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using encode().
     * If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * The following special options are recognized:
     *
     * - `doubleEncode`: whether to double encode HTML entities in `$value`. If `false`, HTML entities in
     *   `$value` will not be further encoded.
     * @return string the generated text area tag
     */
    public static function textarea($name, $value = '', $options = [])
    {
        $options['name'] = $name;
        $doubleEncode = (bool) ArrayHelper::remove($options, 'doubleEncode', true);
        return static::tag('textarea', static::encode($value, $doubleEncode), $options);
    }

    /**
     * Generates a radio button input.
     *
     * @param string|null $name the name attribute.
     * @param bool $checked whether the radio button should be checked.
     * @param array<mixed> $options the tag options in terms of name-value pairs.
     * See booleanInput() for details about accepted attributes.
     * @return string the generated radio button tag
     */
    public static function radio($name, $checked = false, $options = [])
    {
        return static::booleanInput('radio', $name, $checked, $options);
    }

    /**
     * Generates a checkbox input.
     *
     * @param string|null $name the name attribute.
     * @param bool $checked whether the checkbox should be checked.
     * @param array<mixed> $options the tag options in terms of name-value pairs.
     * See booleanInput() for details about accepted attributes.
     * @return string the generated checkbox tag
     */
    public static function checkbox($name, $checked = false, $options = [])
    {
        return static::booleanInput('checkbox', $name, $checked, $options);
    }

    /**
     * Generates a boolean input.
     *
     * @param string $type the input type. This can be either `radio` or `checkbox`.
     * @param string|null $name the name attribute.
     * @param bool $checked whether the checkbox should be checked.
     * @param array<mixed> $options the tag options in terms of name-value pairs. The following options are
     * specially handled:
     *
     * - uncheck: string, the value associated with the uncheck state of the checkbox. When this attribute
     *   is present, a hidden input will be generated so that if the checkbox is not checked and is submitted,
     *   the value of this attribute will still be submitted to the server via the hidden input.
     * - label: string, a label displayed next to the checkbox. It will NOT be HTML-encoded. Therefore you can
     *   pass in HTML code such as an image tag. If this is is coming from end users, you should encode() it to
     *   prevent XSS attacks. When this option is specified, the checkbox will be enclosed by a label tag.
     * - labelOptions: array, the HTML attributes for the label tag. Do not set this option unless you set the
     *   "label" option.
     *
     * The rest of the options will be rendered as the attributes of the resulting checkbox tag. The values will
     * be HTML-encoded using encode(). If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated checkbox tag
     */
    protected static function booleanInput($type, $name, $checked = false, $options = [])
    {
        // 'checked' option has priority over $checked argument
        if (!isset($options['checked'])) {
            $options['checked'] = (bool) $checked;
        }
        $value = self::toTextOrNull(array_key_exists('value', $options) ? $options['value'] : '1');
        if (isset($options['uncheck'])) {
            // add a hidden field so that if the checkbox is not selected, it still submits a value
            $hiddenOptions = [];
            if (isset($options['form'])) {
                $hiddenOptions['form'] = $options['form'];
            }
            // make sure disabled input is not sending any value
            if (!empty($options['disabled'])) {
                $hiddenOptions['disabled'] = $options['disabled'];
            }
            $hidden = static::hiddenInput($name, self::toText($options['uncheck']), $hiddenOptions);
            unset($options['uncheck']);
        } else {
            $hidden = '';
        }
        if (isset($options['label'])) {
            $label = self::toText($options['label']);
            $labelOptions = self::arrayOption($options['labelOptions'] ?? []);
            unset($options['label'], $options['labelOptions']);
            $content = static::label(static::input($type, $name, $value, $options) . ' ' . $label, null, $labelOptions);
            return $hidden . $content;
        }

        return $hidden . static::input($type, $name, $value, $options);
    }

    /**
     * Generates a drop-down list.
     *
     * @param string|null $name the input name
     * @param string|int|float|bool|array<mixed>|\Traversable<mixed>|null $selection the selected value(s).
     * A scalar for single or an array or Traversable for multiple selection(s).
     * @param array<mixed> $items the option data items. The array keys are option values, and the array values
     * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     * For each sub-array, an option group will be generated whose label is the key associated with the
     * sub-array. A list of data models can be converted into this format with ArrayHelper::map().
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
     * the labels will also be HTML-encoded.
     * @param array<mixed> $options the tag options in terms of name-value pairs. The following options are
     * specially handled:
     *
     * - prompt: string, a prompt text to be displayed as the first option. An array overrides the value and
     *   sets other tag attributes:
     *
     *   ```php
     *   ['text' => 'Please select', 'options' => ['value' => 'none', 'class' => 'prompt', 'label' => 'Select']],
     *   ```
     *
     * - options: array, the attributes for the select option tags. The array keys must be valid option values,
     *   and the array values are the extra attributes for the corresponding option tags. For example,
     *
     *   ```php
     *   [
     *       'value1' => ['disabled' => true],
     *       'value2' => ['label' => 'value 2'],
     *   ];
     *   ```
     *
     * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of
     *   'options', except that the array keys represent the optgroup labels specified in $items.
     * - encodeSpaces: bool, whether to encode spaces in option prompt and option value with `&nbsp;` character.
     *   Defaults to false.
     * - encode: bool, whether to encode option prompt and option value characters. Defaults to `true`.
     * - multiple: when not empty, the list is rendered by listBox().
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using encode(). If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated drop-down list tag
     */
    public static function dropDownList($name, $selection = null, $items = [], $options = [])
    {
        if (!empty($options['multiple'])) {
            return static::listBox($name, $selection, $items, $options);
        }
        $options['name'] = $name;
        unset($options['unselect']);
        $selectOptions = static::renderSelectOptions($selection, $items, $options);
        return static::tag('select', "\n" . $selectOptions . "\n", $options);
    }

    /**
     * Generates a list box.
     *
     * @param string|null $name the input name
     * @param string|int|float|bool|array<mixed>|\Traversable<mixed>|null $selection the selected value(s).
     * A scalar for single or an array or Traversable for multiple selection(s).
     * @param array<mixed> $items the option data items. The array keys are option values, and the array values
     * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     * For each sub-array, an option group will be generated whose label is the key associated with the
     * sub-array. A list of data models can be converted into this format with ArrayHelper::map().
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
     * the labels will also be HTML-encoded.
     * @param array<mixed> $options the tag options in terms of name-value pairs. The following options are
     * specially handled:
     *
     * - prompt: string, a prompt text to be displayed as the first option. An array overrides the value and
     *   sets other tag attributes:
     *
     *   ```php
     *   ['text' => 'Please select', 'options' => ['value' => 'none', 'class' => 'prompt', 'label' => 'Select']],
     *   ```
     *
     * - options: array, the attributes for the select option tags. The array keys must be valid option values,
     *   and the array values are the extra attributes for the corresponding option tags. For example,
     *
     *   ```php
     *   [
     *       'value1' => ['disabled' => true],
     *       'value2' => ['label' => 'value 2'],
     *   ];
     *   ```
     *
     * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of
     *   'options', except that the array keys represent the optgroup labels specified in $items.
     * - unselect: string, the value that will be submitted when no option is selected.
     *   When this attribute is set, a hidden field will be generated so that if no option is selected in
     *   multiple mode, we can still obtain the posted unselect value.
     * - encodeSpaces: bool, whether to encode spaces in option prompt and option value with `&nbsp;` character.
     *   Defaults to false.
     * - encode: bool, whether to encode option prompt and option value characters. Defaults to `true`.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using encode(). If a value is null, the corresponding attribute will not be rendered.
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated list box tag
     */
    public static function listBox($name, $selection = null, $items = [], $options = [])
    {
        if (!array_key_exists('size', $options)) {
            $options['size'] = 4;
        }
        if (!empty($options['multiple']) && !empty($name) && substr_compare($name, '[]', -2, 2)) {
            $name .= '[]';
        }
        $options['name'] = $name;
        if (isset($options['unselect'])) {
            // add a hidden field so that if the list box has no option being selected, it still submits a value
            if (!empty($name) && substr_compare($name, '[]', -2, 2) === 0) {
                $name = substr($name, 0, -2);
            }
            $hiddenOptions = [];
            // make sure disabled input is not sending any value
            if (!empty($options['disabled'])) {
                $hiddenOptions['disabled'] = $options['disabled'];
            }
            $hidden = static::hiddenInput($name, self::toText($options['unselect']), $hiddenOptions);
            unset($options['unselect']);
        } else {
            $hidden = '';
        }
        $selectOptions = static::renderSelectOptions($selection, $items, $options);
        return $hidden . static::tag('select', "\n" . $selectOptions . "\n", $options);
    }

    /**
     * Generates a list of checkboxes.
     *
     * A checkbox list allows multiple selection, like listBox().
     * As a result, the corresponding submitted value is an array.
     *
     * @param string $name the name attribute of each checkbox.
     * @param string|int|float|bool|array<mixed>|\Traversable<mixed>|null $selection the selected value(s).
     * A scalar for single or an array or Traversable for multiple selection(s).
     * @param array<mixed> $items the data item used to generate the checkboxes.
     * The array keys are the checkbox values, while the array values are the corresponding labels.
     * @param array<mixed> $options options (name => config) for the checkbox list container tag.
     * The following options are specially handled:
     *
     * - tag: string|false, the tag name of the container element. False to render checkbox without container.
     *   See also tag().
     * - unselect: string, the value that should be submitted when none of the checkboxes is selected.
     *   By setting this option, a hidden input will be generated.
     * - encode: boolean, whether to HTML-encode the checkbox labels. Defaults to true.
     *   This option is ignored if `item` option is set.
     * - strict: boolean, whether the selection is compared strictly (`===`). Defaults to false.
     * - separator: string, the HTML code that separates items.
     * - itemOptions: array, the options for generating the checkbox tag using checkbox().
     * - item: callable, a callback that can be used to customize the generation of the HTML code
     *   corresponding to a single item in $items. The signature of this callback must be:
     *
     *   ```php
     *   function ($index, $label, $name, $checked, $value)
     *   ```
     *
     *   where $index is the zero-based index of the checkbox in the whole list; $label
     *   is the label for the checkbox; and $name, $value and $checked represent the name,
     *   value and the checked status of the checkbox input, respectively.
     *
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated checkbox list
     * @throws InvalidParamException if the `item` option is not callable, or a value cannot be rendered as text.
     */
    public static function checkboxList($name, $selection = null, $items = [], $options = [])
    {
        if (substr($name, -2) !== '[]') {
            $name .= '[]';
        }
        $selection = self::listSelection($selection);

        $formatter = self::itemFormatter(ArrayHelper::remove($options, 'item'));
        $itemOptions = self::arrayOption(ArrayHelper::remove($options, 'itemOptions', []));
        $encode = (bool) ArrayHelper::remove($options, 'encode', true);
        $separator = self::toText(ArrayHelper::remove($options, 'separator', "\n"));
        $tag = self::tagName(ArrayHelper::remove($options, 'tag', 'div'));
        $strict = (bool) ArrayHelper::remove($options, 'strict', false);

        $lines = [];
        $index = 0;
        foreach ($items as $value => $label) {
            $checked = self::isListItemChecked($value, $selection, $strict);
            if ($formatter !== null) {
                $lines[] = self::toText($formatter($index, $label, $name, $checked, $value));
            } else {
                $itemInputOptions = self::listItemOptions($value, $label, $encode, $itemOptions);
                $lines[] = static::checkbox($name, $checked, $itemInputOptions);
            }
            $index++;
        }

        if (isset($options['unselect'])) {
            // add a hidden field so that if the list box has no option being selected, it still submits a value
            $name2 = substr($name, -2) === '[]' ? substr($name, 0, -2) : $name;
            $hiddenOptions = [];
            // make sure disabled input is not sending any value
            if (!empty($options['disabled'])) {
                $hiddenOptions['disabled'] = $options['disabled'];
            }
            $hidden = static::hiddenInput($name2, self::toText($options['unselect']), $hiddenOptions);
            unset($options['unselect'], $options['disabled']);
        } else {
            $hidden = '';
        }

        $visibleContent = implode($separator, $lines);

        if ($tag === false) {
            return $hidden . $visibleContent;
        }

        return $hidden . static::tag($tag, $visibleContent, $options);
    }

    /**
     * Generates a list of radio buttons.
     *
     * A radio button list is like a checkbox list, except that it only allows single selection.
     *
     * @param string|null $name the name attribute of each radio button.
     * @param string|int|float|bool|array<mixed>|\Traversable<mixed>|null $selection the selected value(s).
     * A scalar for single or an array or Traversable for multiple selection(s).
     * @param array<mixed> $items the data item used to generate the radio buttons.
     * The array keys are the radio button values, while the array values are the corresponding labels.
     * @param array<mixed> $options options (name => config) for the radio button list container tag.
     * The following options are specially handled:
     *
     * - tag: string|false, the tag name of the container element. False to render radio buttons without
     *   container. See also tag().
     * - unselect: string, the value that should be submitted when none of the radio buttons is selected.
     *   By setting this option, a hidden input will be generated.
     * - encode: boolean, whether to HTML-encode the checkbox labels. Defaults to true.
     *   This option is ignored if `item` option is set.
     * - strict: boolean, whether the selection is compared strictly (`===`). Defaults to false.
     * - separator: string, the HTML code that separates items.
     * - itemOptions: array, the options for generating the radio button tag using radio().
     * - item: callable, a callback that can be used to customize the generation of the HTML code
     *   corresponding to a single item in $items. The signature of this callback must be:
     *
     *   ```php
     *   function ($index, $label, $name, $checked, $value)
     *   ```
     *
     *   where $index is the zero-based index of the radio button in the whole list; $label
     *   is the label for the radio button; and $name, $value and $checked represent the name,
     *   value and the checked status of the radio button input, respectively.
     *
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated radio button list
     * @throws InvalidParamException if the `item` option is not callable, or a value cannot be rendered as text.
     */
    public static function radioList($name, $selection = null, $items = [], $options = [])
    {
        $selection = self::listSelection($selection);

        $formatter = self::itemFormatter(ArrayHelper::remove($options, 'item'));
        $itemOptions = self::arrayOption(ArrayHelper::remove($options, 'itemOptions', []));
        $encode = (bool) ArrayHelper::remove($options, 'encode', true);
        $separator = self::toText(ArrayHelper::remove($options, 'separator', "\n"));
        $tag = self::tagName(ArrayHelper::remove($options, 'tag', 'div'));
        $strict = (bool) ArrayHelper::remove($options, 'strict', false);

        $hidden = '';
        if (isset($options['unselect'])) {
            // add a hidden field so that if the list box has no option being selected, it still submits a value
            $hiddenOptions = [];
            // make sure disabled input is not sending any value
            if (!empty($options['disabled'])) {
                $hiddenOptions['disabled'] = $options['disabled'];
            }
            $hidden = static::hiddenInput($name, self::toText($options['unselect']), $hiddenOptions);
            unset($options['unselect'], $options['disabled']);
        }

        $lines = [];
        $index = 0;
        foreach ($items as $value => $label) {
            $checked = self::isListItemChecked($value, $selection, $strict);
            if ($formatter !== null) {
                $lines[] = self::toText($formatter($index, $label, $name, $checked, $value));
            } else {
                $itemInputOptions = self::listItemOptions($value, $label, $encode, $itemOptions);
                $lines[] = static::radio($name, $checked, $itemInputOptions);
            }
            $index++;
        }
        $visibleContent = implode($separator, $lines);

        if ($tag === false) {
            return $hidden . $visibleContent;
        }

        return $hidden . static::tag($tag, $visibleContent, $options);
    }

    /**
     * Generates an unordered list.
     *
     * @param array<mixed>|\Traversable<mixed> $items the items for generating the list. Each item
     * generates a single list item. Note that items will be automatically HTML encoded if `$options['encode']`
     * is not set or true.
     * @param array<mixed> $options options (name => config) for the list. The following options are supported:
     *
     * - tag: string, the tag name of the list element. Defaults to `ul`.
     * - encode: boolean, whether to HTML-encode the items. Defaults to true.
     *   This option is ignored if the `item` option is specified.
     * - separator: string, the HTML code that separates items. Defaults to a simple newline (`"\n"`).
     * - itemOptions: array, the HTML attributes for the `li` tags. This option is ignored if the `item` option
     *   is specified.
     * - item: callable, a callback that is used to generate each individual list item.
     *   The signature of this callback must be:
     *
     *   ```php
     *   function ($item, $index)
     *   ```
     *
     *   where $index is the array key corresponding to `$item` in `$items`. The callback should return
     *   the whole list item tag.
     *
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated unordered list. An empty list tag will be returned if `$items` is empty.
     * @throws InvalidParamException if the `item` option is not callable, or a value cannot be rendered as text.
     */
    public static function ul($items, $options = [])
    {
        $tag = self::tagName(ArrayHelper::remove($options, 'tag', 'ul'));
        $encode = (bool) ArrayHelper::remove($options, 'encode', true);
        $formatter = self::itemFormatter(ArrayHelper::remove($options, 'item'));
        $separator = self::toText(ArrayHelper::remove($options, 'separator', "\n"));
        $itemOptions = self::arrayOption(ArrayHelper::remove($options, 'itemOptions', []));

        if (empty($items)) {
            return static::tag($tag, '', $options);
        }

        $results = [];
        foreach ($items as $index => $item) {
            if ($formatter !== null) {
                $results[] = self::toText($formatter($item, $index));
            } else {
                $text = self::toText($item);
                $results[] = static::tag('li', $encode ? static::encode($text) : $text, $itemOptions);
            }
        }

        return static::tag(
            $tag,
            $separator . implode($separator, $results) . $separator,
            $options
        );
    }

    /**
     * Generates an ordered list.
     *
     * @param array<mixed>|\Traversable<mixed> $items the items for generating the list. Each item
     * generates a single list item. Note that items will be automatically HTML encoded if `$options['encode']`
     * is not set or true.
     * @param array<mixed> $options options (name => config) for the list. The following options are supported:
     *
     * - encode: boolean, whether to HTML-encode the items. Defaults to true.
     *   This option is ignored if the `item` option is specified.
     * - itemOptions: array, the HTML attributes for the `li` tags. This option is ignored if the `item` option
     *   is specified.
     * - item: callable, a callback that is used to generate each individual list item.
     *   The signature of this callback must be:
     *
     *   ```php
     *   function ($item, $index)
     *   ```
     *
     *   where $index is the array key corresponding to `$item` in `$items`. The callback should return
     *   the whole list item tag.
     *
     * See renderTagAttributes() for details on how attributes are being rendered.
     * @return string the generated ordered list. An empty list tag will be returned if `$items` is empty.
     * @throws InvalidParamException if the `item` option is not callable, or a value cannot be rendered as text.
     */
    public static function ol($items, $options = [])
    {
        $options['tag'] = 'ol';
        return static::ul($items, $options);
    }

    /**
     * Renders the option tags that can be used by dropDownList() and listBox().
     *
     * @param string|int|float|bool|array<mixed>|\Traversable<mixed>|null $selection the selected value(s).
     * A scalar for single or an array or Traversable for multiple selection(s).
     * @param array<mixed> $items the option data items. The array keys are option values, and the array values
     * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     * For each sub-array, an option group will be generated whose label is the key associated with the
     * sub-array. A list of data models can be converted into this format with ArrayHelper::map().
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
     * the labels will also be HTML-encoded.
     * @param array<mixed> $tagOptions the $options parameter that is passed to the dropDownList() or listBox()
     * call. This method will take out these elements, if any: "prompt", "options", "groups", "encode",
     * "encodeSpaces" and "strict". See dropDownList() for the explanation of these elements.
     * @return string the generated list options
     * @throws InvalidParamException if an option label cannot be rendered as text.
     */
    public static function renderSelectOptions($selection, $items, &$tagOptions = [])
    {
        $selection = self::normalizeSelection($selection);

        $lines = [];
        $encodeSpaces = ArrayHelper::remove($tagOptions, 'encodeSpaces', false);
        $encode = ArrayHelper::remove($tagOptions, 'encode', true);
        $strict = ArrayHelper::remove($tagOptions, 'strict', false);
        if (isset($tagOptions['prompt'])) {
            $lines[] = self::renderPromptOption($tagOptions['prompt'], (bool) $encode, (bool) $encodeSpaces);
        }

        $options = self::arrayOption($tagOptions['options'] ?? []);
        $groups = self::arrayOption($tagOptions['groups'] ?? []);
        unset($tagOptions['prompt'], $tagOptions['options'], $tagOptions['groups']);
        $options['encodeSpaces'] = ArrayHelper::getValue($options, 'encodeSpaces', $encodeSpaces);
        $options['encode'] = ArrayHelper::getValue($options, 'encode', $encode);

        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $groupAttrs = self::arrayOption($groups[$key] ?? []);
                if (!isset($groupAttrs['label'])) {
                    $groupAttrs['label'] = $key;
                }
                $attrs = [
                    'options' => $options,
                    'groups' => $groups,
                    'encodeSpaces' => $encodeSpaces,
                    'encode' => $encode,
                    'strict' => $strict,
                ];
                $content = static::renderSelectOptions($selection, $value, $attrs);
                $lines[] = static::tag('optgroup', "\n" . $content . "\n", $groupAttrs);
            } else {
                $attrs = self::arrayOption($options[$key] ?? []);
                $attrs['value'] = (string) $key;
                if (!array_key_exists('selected', $attrs)) {
                    $attrs['selected'] = self::isOptionSelected($key, $selection, (bool) $strict);
                }
                $text = self::toText($value);
                if ($encode) {
                    $text = static::encode($text);
                }
                if ($encodeSpaces) {
                    $text = str_replace(' ', '&nbsp;', $text);
                }
                $lines[] = static::tag('option', $text, $attrs);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Renders the prompt option of a select list.
     *
     * @param mixed $prompt the prompt text, or an array with the text under "text" and the option tag
     * attributes under "options".
     * @param bool $encode whether to HTML-encode the prompt text.
     * @param bool $encodeSpaces whether to replace the spaces of the prompt text with `&nbsp;`.
     * @return string the generated option tag
     * @throws InvalidParamException if the prompt text cannot be rendered as text.
     */
    private static function renderPromptOption(mixed $prompt, bool $encode, bool $encodeSpaces): string
    {
        $promptOptions = ['value' => ''];
        if (is_array($prompt)) {
            $promptText = self::toText($prompt['text'] ?? null);
            $promptOptions = array_merge($promptOptions, self::arrayOption($prompt['options'] ?? []));
        } else {
            $promptText = self::toText($prompt);
        }
        if ($encode) {
            $promptText = static::encode($promptText);
        }
        if ($encodeSpaces) {
            $promptText = str_replace(' ', '&nbsp;', $promptText);
        }

        return static::tag('option', $promptText, $promptOptions);
    }

    /**
     * Tells whether a select option is selected.
     *
     * @param int|string $key the option value.
     * @param array<string>|string|int|float|null $selection the selection normalised by normalizeSelection().
     * @param bool $strict whether the option value is compared with the selection as a string.
     * @return bool
     */
    private static function isOptionSelected(
        int|string $key,
        array|string|int|float|null $selection,
        bool $strict
    ): bool {
        if ($selection === null) {
            return false;
        }
        if (is_array($selection)) {
            return ArrayHelper::isIn((string)$key, $selection, $strict);
        }
        if ($key === '' || $selection === '') {
            return $selection === $key;
        }
        if ($strict) {
            return !strcmp((string)$key, (string)$selection);
        }

        return $selection == $key;
    }

    /**
     * Normalises the selection of a select list for comparison with the option values.
     *
     * An array or Traversable becomes a list of strings (a boolean as '1' or '0'), a boolean becomes '1' or
     * '0', and a Stringable object its string. A value that cannot be compared with an option value selects
     * nothing.
     *
     * @param mixed $selection the selection as given to renderSelectOptions().
     * @return array<string>|string|int|float|null
     * @throws InvalidParamException if a selected value cannot be rendered as text.
     */
    private static function normalizeSelection(mixed $selection): array|string|int|float|null
    {
        if (is_array($selection) || $selection instanceof \Traversable) {
            $normalizedSelection = [];
            foreach (ArrayHelper::toArray($selection) as $selectionItem) {
                $normalizedSelection[] = is_bool($selectionItem)
                    ? self::booleanText($selectionItem)
                    : self::toText($selectionItem);
            }
            return $normalizedSelection;
        }
        if (is_bool($selection)) {
            return self::booleanText($selection);
        }
        if (is_string($selection) || is_int($selection) || is_float($selection)) {
            return $selection;
        }
        if ($selection instanceof \Stringable) {
            return (string) $selection;
        }

        return null;
    }

    /**
     * Returns '1' for true and '0' for false, the way a selected boolean matches an option value.
     *
     * @param bool $value
     * @return string
     */
    private static function booleanText(bool $value): string
    {
        return $value ? '1' : '0';
    }

    /**
     * Normalises the selection of a checkbox or radio list.
     *
     * An array or Traversable becomes an array of strings; any other value but null becomes a string.
     *
     * @param mixed $selection the selection as given to checkboxList() or radioList().
     * @return array<string>|string|null
     * @throws InvalidParamException if a selected value cannot be rendered as text.
     */
    private static function listSelection(mixed $selection): array|string|null
    {
        if (is_array($selection) || $selection instanceof \Traversable) {
            return self::texts(ArrayHelper::toArray($selection));
        }

        return $selection === null ? null : self::toText($selection);
    }

    /**
     * Tells whether an item of a checkbox or radio list is checked.
     *
     * @param int|string $value the item value.
     * @param array<string>|string|null $selection the selection normalised by listSelection().
     * @param bool $strict whether a list selection is compared strictly (`===`).
     * @return bool
     */
    private static function isListItemChecked(int|string $value, array|string|null $selection, bool $strict): bool
    {
        if ($selection === null) {
            return false;
        }
        if (is_array($selection)) {
            return ArrayHelper::isIn((string)$value, $selection, $strict);
        }

        return !strcmp((string)$value, $selection);
    }

    /**
     * Builds the input options of one checkbox or radio list item: the item options override value and label.
     *
     * @param int|string $value the item value.
     * @param mixed $label the item label.
     * @param bool $encode whether to HTML-encode the label.
     * @param array<mixed> $itemOptions the `itemOptions` of the list.
     * @return array<mixed>
     * @throws InvalidParamException if the label cannot be rendered as text.
     */
    private static function listItemOptions(int|string $value, mixed $label, bool $encode, array $itemOptions): array
    {
        return array_merge([
            'value' => $value,
            'label' => $encode ? static::encode(self::toText($label)) : $label,
        ], $itemOptions);
    }

    /**
     * Returns the `item` option of a list as a callable, or null when it is not set.
     *
     * @param mixed $formatter the value of the `item` option.
     * @return callable|null
     * @throws InvalidParamException if the option is set to something that is not callable.
     */
    private static function itemFormatter(mixed $formatter): ?callable
    {
        if ($formatter === null || is_callable($formatter)) {
            return $formatter;
        }

        throw new InvalidParamException('The "item" option must be a callable.');
    }

    /**
     * Returns the `tag` option of a list as a tag name for tag(): a string, a boolean or null.
     *
     * @param mixed $tag the value of the `tag` option.
     * @return string|bool|null
     * @throws InvalidParamException if the value cannot be rendered as text.
     */
    private static function tagName(mixed $tag): string|bool|null
    {
        if ($tag === null || is_string($tag) || is_bool($tag)) {
            return $tag;
        }

        return self::toText($tag);
    }

    /**
     * Returns an option value that must be an array, or an empty array when it is not one.
     *
     * @param mixed $value the option value.
     * @return array<mixed>
     */
    private static function arrayOption(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * Converts a value to the text PHP would print for it: a scalar, null or a Stringable object.
     *
     * @param mixed $value
     * @return string
     * @throws InvalidParamException if the value is an array, a resource or an object without __toString().
     */
    private static function toText(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if ($value === null || is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw new InvalidParamException(
            sprintf('A value of type %s cannot be rendered as text.', get_debug_type($value))
        );
    }

    /**
     * Converts a value to text like toText(), keeping null as null.
     *
     * @param mixed $value
     * @return string|null
     * @throws InvalidParamException if the value is an array, a resource or an object without __toString().
     */
    private static function toTextOrNull(mixed $value): ?string
    {
        return $value === null ? null : self::toText($value);
    }

    /**
     * Converts every value of an array to text with toText(), keeping the keys.
     *
     * @param array<mixed> $values
     * @return array<string>
     * @throws InvalidParamException if a value cannot be rendered as text.
     */
    private static function texts(array $values): array
    {
        $texts = [];
        foreach ($values as $key => $value) {
            $texts[$key] = self::toText($value);
        }

        return $texts;
    }

    /**
     * Removes the values whose text repeats an earlier one, keeping keys and values, as array_unique() does.
     *
     * @param array<mixed> $values
     * @return array<mixed>
     * @throws InvalidParamException if a value cannot be rendered as text.
     */
    private static function uniqueByText(array $values): array
    {
        $seen = [];
        $unique = [];
        foreach ($values as $key => $value) {
            $text = self::toText($value);
            if (!isset($seen[$text])) {
                $seen[$text] = true;
                $unique[$key] = $value;
            }
        }

        return $unique;
    }

    /**
     * Removes the values whose text is one of the given texts, keeping keys and values, as array_diff() does.
     *
     * @param array<mixed> $values
     * @param array<string> $remove
     * @return array<mixed>
     * @throws InvalidParamException if a value cannot be rendered as text.
     */
    private static function diffByText(array $values, array $remove): array
    {
        $removed = [];
        foreach ($remove as $text) {
            $removed[$text] = true;
        }
        $kept = [];
        foreach ($values as $key => $value) {
            if (!isset($removed[self::toText($value)])) {
                $kept[$key] = $value;
            }
        }

        return $kept;
    }

    /**
     * Renders the HTML tag attributes.
     *
     * Attributes whose values are of boolean type will be treated as
     * [boolean attributes](http://www.w3.org/TR/html5/infrastructure.html#boolean-attributes).
     *
     * Attributes whose values are null will not be rendered.
     *
     * The values of attributes will be HTML-encoded using encode().
     *
     * The "data" attribute is specially handled when it is receiving an array value. In this case,
     * the array will be "expanded" and a list data attributes will be rendered. For example,
     * if `'data' => ['id' => 1, 'name' => 'yii']`, then this will be rendered:
     * `data-id="1" data-name="yii"`.
     * Additionally `'data' => ['params' => ['id' => 1, 'name' => 'yii'], 'status' => 'ok']` will be rendered as:
     * `data-params='{"id":1,"name":"yii"}' data-status="ok"`.
     *
     * @param array<mixed> $attributes attributes to be rendered. The attribute values will be HTML-encoded
     * using encode().
     * @return string the rendering result. If the attributes are not empty, they will be rendered
     * into a string with a leading white space (so that it can be directly appended to the tag name
     * in a tag. If there is no attribute, an empty string will be returned.
     * @throws InvalidParamException if an attribute value cannot be rendered as text.
     */
    public static function renderTagAttributes($attributes)
    {
        if (count($attributes) > 1) {
            $sorted = [];
            foreach (static::$attributeOrder as $name) {
                if (isset($attributes[$name])) {
                    $sorted[$name] = $attributes[$name];
                }
            }
            $attributes = array_merge($sorted, $attributes);
        }
        $html = '';
        foreach ($attributes as $name => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= " $name";
                }
            } elseif (is_array($value)) {
                if (in_array($name, static::$dataAttributes)) {
                    $html .= self::renderDataAttributes($name, $value);
                } elseif ($name === 'class') {
                    if (empty($value)) {
                        continue;
                    }
                    if (static::$normalizeClassAttribute === true && count($value) > 1) {
                        // removes duplicate classes
                        $value = array_unique(explode(' ', implode(' ', self::texts($value))));
                    }
                    $html .= " $name=\"" . static::encode(implode(' ', self::texts(array_filter($value)))) . '"';
                } elseif ($name === 'style') {
                    if (empty($value)) {
                        continue;
                    }
                    $html .= " $name=\"" . static::encode(static::cssStyleFromArray($value)) . '"';
                } else {
                    $html .= " $name='" . Json::htmlEncode($value) . "'";
                }
            } elseif ($value !== null) {
                $html .= " $name=\"" . static::encode(self::toText($value)) . '"';
            }
        }

        return $html;
    }

    /**
     * Renders an expanded attribute such as `data`: one `name-key` attribute per array element.
     *
     * An array element is rendered as JSON, a boolean element as a boolean attribute, and a null element is
     * skipped.
     *
     * @param int|string $name the attribute name, e.g. `data`.
     * @param array<mixed> $values the attribute value.
     * @return string the rendered attributes, each with a leading white space.
     * @throws InvalidParamException if an element cannot be rendered as text.
     */
    private static function renderDataAttributes(int|string $name, array $values): string
    {
        $html = '';
        foreach ($values as $n => $v) {
            if (is_array($v)) {
                $html .= " $name-$n='" . Json::htmlEncode($v) . "'";
            } elseif (is_bool($v)) {
                if ($v) {
                    $html .= " $name-$n";
                }
            } elseif ($v !== null) {
                $html .= " $name-$n=\"" . static::encode(self::toText($v)) . '"';
            }
        }

        return $html;
    }

    /**
     * Adds a CSS class (or several classes) to the specified options.
     *
     * If the CSS class is already in the options, it will not be added again.
     * If class specification at given options is an array, and some class placed there with the named (string)
     * key, overriding of such key will have no effect. For example:
     *
     * ```php
     * $options = ['class' => ['persistent' => 'initial']];
     * Html::addCssClass($options, ['persistent' => 'override']);
     * var_dump($options['class']); // outputs: array('persistent' => 'initial');
     * ```
     *
     * @param array<mixed> $options the options to be modified.
     * @param string|array<string> $class the CSS class(es) to be added
     * @return void
     * @throws InvalidParamException if an existing class cannot be rendered as text.
     */
    public static function addCssClass(&$options, $class)
    {
        if (isset($options['class'])) {
            if (is_array($options['class'])) {
                $options['class'] = self::mergeCssClasses($options['class'], (array) $class);
            } else {
                $classes = preg_split('/\s+/', self::toText($options['class']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $options['class'] = implode(' ', self::texts(self::mergeCssClasses($classes, (array) $class)));
            }
        } else {
            $options['class'] = $class;
        }
    }

    /**
     * Merges already existing CSS classes with new one.
     *
     * This method provides the priority for named existing classes over additional.
     *
     * @param array<mixed> $existingClasses already existing CSS classes.
     * @param array<string> $additionalClasses CSS classes to be added.
     * @return array<mixed> merge result.
     * @throws InvalidParamException if a class cannot be rendered as text.
     */
    private static function mergeCssClasses(array $existingClasses, array $additionalClasses): array
    {
        foreach ($additionalClasses as $key => $class) {
            if (is_int($key) && !in_array($class, $existingClasses)) {
                $existingClasses[] = $class;
            } elseif (!isset($existingClasses[$key])) {
                $existingClasses[$key] = $class;
            }
        }

        return static::$normalizeClassAttribute ? self::uniqueByText($existingClasses) : $existingClasses;
    }

    /**
     * Removes a CSS class from the specified options.
     *
     * @param array<mixed> $options the options to be modified.
     * @param string|array<string> $class the CSS class(es) to be removed
     * @return void
     * @throws InvalidParamException if an existing class cannot be rendered as text.
     * @see addCssClass()
     */
    public static function removeCssClass(&$options, $class)
    {
        if (isset($options['class'])) {
            if (is_array($options['class'])) {
                $classes = self::diffByText($options['class'], (array) $class);
                if (empty($classes)) {
                    unset($options['class']);
                } else {
                    $options['class'] = $classes;
                }
            } else {
                $classes = preg_split('/\s+/', self::toText($options['class']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $classes = array_diff($classes, (array) $class);
                if (empty($classes)) {
                    unset($options['class']);
                } else {
                    $options['class'] = implode(' ', $classes);
                }
            }
        }
    }

    /**
     * Adds the specified CSS style to the HTML options.
     *
     * If the options already contain a `style` element, the new style will be merged
     * with the existing one. If a CSS property exists in both the new and the old styles,
     * the old one may be overwritten if `$overwrite` is true.
     *
     * For example,
     *
     * ```php
     * Html::addCssStyle($options, 'width: 100px; height: 200px');
     * ```
     *
     * @param array<mixed> $options the HTML options to be modified.
     * @param string|array<mixed> $style the new style string (e.g. `'width: 100px; height: 200px'`) or
     * array (e.g. `['width' => '100px', 'height' => '200px']`).
     * @param bool $overwrite whether to overwrite existing CSS properties if the new style
     * contain them too.
     * @return void
     * @throws InvalidParamException if the existing style cannot be rendered as text.
     * @see removeCssStyle()
     * @see cssStyleFromArray()
     * @see cssStyleToArray()
     */
    public static function addCssStyle(&$options, $style, $overwrite = true)
    {
        if (!empty($options['style'])) {
            $oldStyle = is_array($options['style'])
                ? $options['style']
                : static::cssStyleToArray(self::toText($options['style']));
            $newStyle = is_array($style) ? $style : static::cssStyleToArray($style);
            if (!$overwrite) {
                foreach ($newStyle as $property => $value) {
                    if (isset($oldStyle[$property])) {
                        unset($newStyle[$property]);
                    }
                }
            }
            $style = array_merge($oldStyle, $newStyle);
        }
        $options['style'] = is_array($style) ? static::cssStyleFromArray($style) : $style;
    }

    /**
     * Removes the specified CSS style from the HTML options.
     *
     * For example,
     *
     * ```php
     * Html::removeCssStyle($options, ['width', 'height']);
     * ```
     *
     * @param array<mixed> $options the HTML options to be modified.
     * @param string|array<string> $properties the CSS properties to be removed. You may use a string
     * if you are removing a single property.
     * @return void
     * @throws InvalidParamException if the existing style cannot be rendered as text.
     * @see addCssStyle()
     */
    public static function removeCssStyle(&$options, $properties)
    {
        if (!empty($options['style'])) {
            $style = is_array($options['style'])
                ? $options['style']
                : static::cssStyleToArray(self::toText($options['style']));
            foreach ((array) $properties as $property) {
                unset($style[$property]);
            }
            $options['style'] = static::cssStyleFromArray($style);
        }
    }

    /**
     * Converts a CSS style array into a string representation.
     *
     * For example,
     *
     * ```php
     * print_r(Html::cssStyleFromArray(['width' => '100px', 'height' => '200px']));
     * // will display: 'width: 100px; height: 200px;'
     * ```
     *
     * @param array<mixed> $style the CSS style array. The array keys are the CSS property names,
     * and the array values are the corresponding CSS property values.
     * @return string|null the CSS style string, or null when the style is empty (so that no "style"
     * attribute is rendered).
     * @throws InvalidParamException if a property value cannot be rendered as text.
     */
    public static function cssStyleFromArray(array $style)
    {
        $result = '';
        foreach ($style as $name => $value) {
            $result .= $name . ': ' . self::toText($value) . '; ';
        }
        // return null if empty to avoid rendering the "style" attribute
        return $result === '' ? null : rtrim($result);
    }

    /**
     * Converts a CSS style string into an array representation.
     *
     * The array keys are the CSS property names, and the array values
     * are the corresponding CSS property values.
     *
     * For example,
     *
     * ```php
     * print_r(Html::cssStyleToArray('width: 100px; height: 200px;'));
     * // will display: ['width' => '100px', 'height' => '200px']
     * ```
     *
     * @param string $style the CSS style string
     * @return array<string, string> the array representation of the CSS style
     */
    public static function cssStyleToArray($style)
    {
        $result = [];
        foreach (explode(';', $style) as $property) {
            $property = explode(':', $property);
            if (count($property) > 1) {
                $result[trim($property[0])] = trim($property[1]);
            }
        }

        return $result;
    }

    /**
     * Returns the real attribute name from the given attribute expression.
     *
     * An attribute expression is an attribute name prefixed and/or suffixed with array indexes.
     * It is mainly used in tabular data input and/or input of array type. Below are some examples:
     *
     * - `[0]content` is used in tabular data input to represent the "content" attribute
     *   for the first model in tabular input;
     * - `dates[0]` represents the first array element of the "dates" attribute;
     * - `[0]dates[0]` represents the first array element of the "dates" attribute
     *   for the first model in tabular input.
     *
     * If `$attribute` has neither prefix nor suffix, it will be returned back without change.
     *
     * @param string $attribute the attribute name or expression
     * @return string the attribute name without prefix and suffix.
     * @throws InvalidParamException if the attribute name contains non-word characters.
     */
    public static function getAttributeName($attribute)
    {
        if (preg_match(static::$attributeRegex, $attribute, $matches)) {
            return $matches[2];
        }

        throw new InvalidParamException('Attribute name must contain word characters only.');
    }

    /**
     * Converts input name to ID.
     *
     * For example, if `$name` is `Post[content]`, this method will return `post-content`.
     *
     * @param string $name the input name
     * @return string the generated input ID
     */
    public static function getInputIdByName($name)
    {
        $name = mb_strtolower($name, 'UTF-8');
        return str_replace(['[]', '][', '[', ']', ' ', '.', '--'], ['', '-', '-', '', '-', '-', '-'], $name);
    }

    /**
     * Converts a PHP regular expression into a JavaScript regular expression literal.
     *
     * `\x` escapes become `\u` escapes, any delimiter becomes `/`, and only the `i`, `g`, `m` and `u` flags
     * are kept.
     *
     * @param string $regexp the regular expression to be escaped.
     * @return string the escaped result.
     */
    public static function escapeJsRegularExpression($regexp)
    {
        $pattern = (string) preg_replace('/\\\\x\{?([0-9a-fA-F]+)\}?/', '\u$1', $regexp);
        $deliminator = substr($pattern, 0, 1);
        $pos = strrpos($pattern, $deliminator, 1);
        $flag = substr($pattern, $pos + 1);
        if ($deliminator !== '/') {
            $pattern = '/' . str_replace('/', '\\/', substr($pattern, 1, $pos - 1)) . '/';
        } else {
            $pattern = substr($pattern, 0, $pos + 1);
        }
        if (!empty($flag)) {
            $pattern .= preg_replace('/[^igmu]/', '', $flag);
        }
        return $pattern;
    }
}
