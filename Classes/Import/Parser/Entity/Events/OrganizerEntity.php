<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Events;

use WerkraumMedia\ThueCat\Import\Parser\Entity\AbstractEntity;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

/**
 * Child of EventEntity: the organizer behind schema:organizer, written into
 * tx_events_domain_model_organizer.
 *
 * Identity is ours. ext:events offers none for organizers — no native hash as
 * Location has — so the remote_id this builds is what a re-import matches on,
 * and the column comes from our own TCA override. Rows without one belong to
 * destination.data and are never touched.
 */
class OrganizerEntity extends AbstractEntity
{
    public const TABLE = 'tx_events_domain_model_organizer';

    /** Columns feeding the generated identity, in a fixed order. */
    protected const IDENTITY_FIELDS = ['name', 'street', 'zip', 'city', 'phone', 'email', 'web'];

    protected string $remote_id = '';
    protected string $name = '';
    protected string $street = '';
    protected string $zip = '';
    protected string $city = '';
    protected string $phone = '';
    protected string $email = '';
    protected string $web = '';

    /**
     * Bypasses the parse() path: EventEntity hands over the inline organizer node.
     *
     * @param array<string, mixed> $node the schema:organizer node
     */
    public function configure(array $node, string $language): void
    {
        $address = $node['schema:address'] ?? [];
        /** @var array<string, mixed> $address JSON-LD nodes are string-keyed. */
        $address = is_array($address) ? $address : [];

        $this->name = $this->extractValue($node['schema:name'] ?? null, $language);
        $this->web = $this->extractValue($node['schema:url'] ?? null, $language);
        $this->street = $this->extractValue($address['schema:streetAddress'] ?? null, $language);
        $this->zip = $this->extractValue($address['schema:postalCode'] ?? null, $language);
        $this->city = $this->extractValue($address['schema:addressLocality'] ?? null, $language);
        $this->phone = $this->extractValue($address['schema:telephone'] ?? null, $language);
        $this->email = $this->extractValue($address['schema:email'] ?? null, $language);

        $this->remote_id = $this->generateRemoteId();
    }

    /** No-op: manufactured by the parent, never dispatched from a node. */
    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
    }

    /** Empty so the Parser's @type dispatch never picks this up. */
    public function handlesTypes(): array
    {
        return [];
    }

    public function getGeneratedRemoteId(): string
    {
        return $this->remote_id;
    }

    /**
     * Whether the organizer carries anything at all
     */
    public function isValid(): bool
    {
        foreach (self::IDENTITY_FIELDS as $field) {
            if ($this->{$field} !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * The organizer arrives as a blank node whose @id is regenerated per
     * delivery, so the payload offers no stable identifier to key on.
     */
    protected function generateRemoteId(): string
    {
        $values = [];
        foreach (self::IDENTITY_FIELDS as $field) {
            $values[] = $this->{$field};
        }

        return 'thuecat:organizer:' . hash('sha256', implode(',', $values));
    }
}
