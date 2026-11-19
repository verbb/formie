<?php

use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;
use verbb\formie\models\Notification;
use verbb\formie\references\ReferenceOutputContext;

// The test runtime's entry type has no title field, so titles are set directly.
function elementSelectorTitled(int $id, string $title): Entry
{
    Craft::$app->getDb()->createCommand()->update('{{%elements_sites}}', ['title' => $title], ['elementId' => $id])->execute();

    return Entry::find()->id($id)->status(null)->one();
}

function elementSelectorEntry(): Entry
{
    $entry = Entry::find()->section('formieTestEntries')->status(null)->one();

    if (!$entry) {
        test()->markTestSkipped('Requires the seeded Formie test entry.');
    }

    return elementSelectorTitled($entry->id, 'Annual <appeal>');
}

function elementSelectorSubmission(string $fieldType, array $ids, array $config = []): array
{
    $form = formie()->form()->{$fieldType . 'Field'}('picked', ['label' => 'Picked', ...$config])->create();
    $submission = formie()->submission($form)->with(['picked' => $ids])->save();

    return [Submission::find()->id($submission->id)->status(null)->one(), (string)$form->getFieldByHandle('picked')->reference];
}

function elementSelectorHtml(Submission $submission, string $content): string
{
    return References::parseContent($content, $submission, ['outputContext' => ReferenceOutputContext::Html, 'allowFieldHtml' => true]);
}

it('writes a Link as a link to the entry in HTML content', function (): void {
    $entry = elementSelectorEntry();
    [$submission, $ref] = elementSelectorSubmission('entries', [$entry->id]);

    expect(elementSelectorHtml($submission, "<p>Thanks for supporting {field:$ref:link}.</p>"))
        ->toBe('<p>Thanks for supporting <a href="' . htmlspecialchars($entry->getUrl()) . '">' . htmlspecialchars($entry->title) . '</a>.</p>')
        ->and(elementSelectorHtml($submission, "<p>{field:$ref:cpLink}</p>"))
        ->toContain('<a href="' . htmlspecialchars($entry->getCpEditUrl()) . '">');
});

it('sends a Link in a real notification email', function (): void {
    $entry = elementSelectorEntry();
    [$submission, $ref] = elementSelectorSubmission('entries', [$entry->id]);

    $render = static fn(string $selector): array => Formie::$plugin->getEmails()->renderEmail(new Notification([
        'name' => 'Links', 'handle' => 'links', 'to' => 'staff@example.test', 'from' => 'sender@example.test',
        'subject' => "About {field:$ref:$selector}",
        'content' => "<p>Thanks for supporting {field:$ref:$selector}.</p>",
    ]), $submission);
    $result = $render('link');

    // The subject gets the label, exactly as the Title selector writes it there.
    expect($result)->not->toHaveKey('error')
        ->and($result['email']->getSymfonyEmail()->getHtmlBody())->toContain('<a href="' . htmlspecialchars($entry->getUrl()) . '">Annual &lt;appeal&gt;</a>')
        ->and($result['email']->getSymfonyEmail()->getSubject())->toBe($render('title')['email']->getSymfonyEmail()->getSubject())
        ->and($result['email']->getSymfonyEmail()->getSubject())->toStartWith('About Annual');
});

it('gives a Link as its label outside HTML text', function (): void {
    $entry = elementSelectorEntry();
    [$submission, $ref] = elementSelectorSubmission('entries', [$entry->id]);

    expect(References::parseContent("Picked {field:$ref:link}", $submission))->toBe('Picked ' . $entry->title)
        ->and(References::parseValue("{field:$ref:link}", $submission))->toBe($entry->title)
        // Inside an attribute, a link would break the tag, so the label is written as attribute text.
        ->and(elementSelectorHtml($submission, "<a title=\"{field:$ref:link}\">x</a>"))->toBe('<a title="' . htmlspecialchars($entry->title) . '">x</a>');
});

it('escapes a Link label and leaves out the link for an element without a URL', function (): void {
    $user = new User(['username' => 'link-' . bin2hex(random_bytes(4)), 'email' => 'link-' . bin2hex(random_bytes(4)) . '@example.test', 'fullName' => '<b>Ada</b>', 'active' => true, 'pending' => false]);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    [$submission, $ref] = elementSelectorSubmission('users', [$user->id], ['labelSource' => 'fullName']);

    expect(elementSelectorHtml($submission, "<p>{field:$ref:link}</p>"))->toBe('<p>&lt;b&gt;Ada&lt;/b&gt;</p>');
});

it('joins Links for several entries', function (): void {
    $entry = elementSelectorEntry();
    $second = new Entry(['sectionId' => $entry->sectionId, 'typeId' => $entry->typeId, 'title' => 'Second linked entry', 'slug' => 'second-linked-' . bin2hex(random_bytes(3))]);
    expect(Craft::$app->getElements()->saveElement($second))->toBeTrue();
    $second = elementSelectorTitled($second->id, 'Second linked entry');
    [$submission, $ref] = elementSelectorSubmission('entries', [$entry->id, $second->id]);

    $html = elementSelectorHtml($submission, "<p>{field:$ref:link}</p>");

    expect(substr_count($html, '<a href='))->toBe(2)
        ->and($html)->toContain('</a>, <a href=')
        ->and(elementSelectorHtml($submission, "<p>{field:$ref:link;index=1}</p>"))->toContain('Second linked entry')
        ->and(elementSelectorHtml($submission, "<p>{field:$ref:link;index=1}</p>"))->not->toContain(htmlspecialchars($entry->title) . '</a>');
});

it('resolves entry attributes', function (): void {
    $entry = elementSelectorEntry();
    [$submission, $ref] = elementSelectorSubmission('entries', [$entry->id]);
    $value = static fn(string $selector): mixed => References::parseValue("{field:$ref:$selector}", $submission);

    expect($value('id'))->toBe($entry->id)
        ->and($value('slug'))->toBe($entry->slug)
        ->and($value('section'))->toBe($entry->getSection()->name)
        ->and($value('entryType'))->toBe($entry->getType()->name)
        ->and($value('status'))->toBe(Entry::statuses()[$entry->getStatus()])
        ->and($value('postDate'))->toBe($entry->postDate ? Craft::$app->getFormatter()->asDate($entry->postDate, 'short') : null);
});

it('resolves category, tag and user attributes', function (): void {
    $category = Category::find()->status(null)->one();
    $tag = Tag::find()->status(null)->one();
    $user = new User(['username' => 'attr-' . bin2hex(random_bytes(4)), 'email' => 'attr-' . bin2hex(random_bytes(4)) . '@example.test', 'fullName' => 'Grace Hopper', 'active' => true, 'pending' => false]);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    if (!$category || !$tag) {
        test()->markTestSkipped('Requires the seeded category and tag.');
    }

    [$categorySubmission, $categoryRef] = elementSelectorSubmission('categories', [$category->id]);
    [$tagSubmission, $tagRef] = elementSelectorSubmission('tags', [$tag->id]);
    [$userSubmission, $userRef] = elementSelectorSubmission('users', [$user->id]);

    expect(References::parseValue("{field:$categoryRef:group}", $categorySubmission))->toBe($category->getGroup()->name)
        ->and(References::parseValue("{field:$categoryRef:slug}", $categorySubmission))->toBe($category->slug)
        ->and(References::parseValue("{field:$tagRef:group}", $tagSubmission))->toBe($tag->getGroup()->name)
        ->and(References::parseValue("{field:$userRef:name}", $userSubmission))->toBe('Grace Hopper')
        ->and(References::parseValue("{field:$userRef:username}", $userSubmission))->toBe($user->username);
});

it('still refuses element properties that are not offered', function (string $selector): void {
    $user = new User(['username' => 'refuse-' . bin2hex(random_bytes(4)), 'email' => 'refuse-' . bin2hex(random_bytes(4)) . '@example.test', 'active' => true, 'pending' => false]);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    [$submission, $ref] = elementSelectorSubmission('users', [$user->id]);

    expect(References::parseContent("[{field:$ref:$selector}]", $submission))->not->toContain($user->email)
        ->and(References::parseContent("[{field:$ref:$selector}]", $submission))->not->toContain('$2y$');
})->with(['email', 'password', 'section', 'authorId']);

it('offers the new selectors to the variable picker', function (): void {
    $entries = new verbb\formie\fields\Entries();
    $users = new verbb\formie\fields\Users();
    $selectors = static fn($field): array => array_column(array_map(static fn($value): array => $value->toArray(), $field->referenceValues()), 'supportsVariablePicker', 'selector');

    expect($selectors($entries))->toMatchArray(['link' => true, 'cpLink' => true, 'id' => true, 'section' => true, 'entryType' => true, 'author' => true])
        ->and($selectors($users))->toMatchArray(['link' => true, 'name' => true, 'username' => true])
        ->and($selectors($users))->not->toHaveKey('email');

    // Outside rich text a link only gives its label, so the picker keeps it to rich text.
    $link = array_values(array_filter($entries->referenceValues(), static fn($value): bool => $value->selector === 'link'))[0];
    expect($link->toArray()['meta'])->toBe(['usages' => ['richText']]);
});
