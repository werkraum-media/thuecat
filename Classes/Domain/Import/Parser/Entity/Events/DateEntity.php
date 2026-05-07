<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\Events;

use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\AbstractEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\ParserContext;

// Inline 1:n child of EventEntity: one row per occurrence in
// tx_events_domain_model_date. Manufactured directly by EventEntity (never
// resolved from a JSON-LD @type) and pushed into the payload via the
// Parser's getChildren() flush after the parent is staged.
//
// Its `event` FK is wired by the Resolver: configure() stages the parent's
// remote_id under the 'event' transient bucket; Resolver looks up the parent
// in the same payload and writes the parent's NEW…/uid into the `event`
// column.
//
// remote_id pattern: <eventRemoteId>::date::<startISO>. Deterministic so
// re-imports upsert the same Date row instead of accumulating duplicates.
class DateEntity extends AbstractEntity
{
    public string $table = 'tx_events_domain_model_date';

    protected string $remote_id = '';
    protected string $start = '';
    protected string $end = '';
    protected string $canceled = 'no';

    /**
     * Bypasses the JSON-LD parse() path. EventEntity already shaped this row
     * during its own parse(); we just store the values and stage the parent
     * back-reference for the Resolver.
     */
    public function configure(string $eventRemoteId, string $start, string $end, bool $canceled): void
    {
        $this->remote_id = $eventRemoteId . '::date::' . $start;
        $this->start = $start;
        $this->end = $end;
        $this->canceled = $canceled ? 'canceled' : 'no';

        // Resolver dereferences this to the parent's NEW…/uid via BUCKET_MAP
        // and writes it into the `event` column of this row.
        $this->recordTransient('event', $eventRemoteId);
    }

    /**
     * No-op: this entity is never dispatched by the Parser from a JSON-LD
     * node. EventEntity calls configure() directly. The interface still
     * requires the method, so we override it with an empty body.
     */
    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        // Intentionally empty — see class docblock.
    }

    /**
     * Empty so the Parser's @type → entity dispatch never picks this up.
     * Children are flushed into the payload only via Parser::parseNode's
     * getChildren() loop, after EventEntity has manufactured them.
     */
    public function handlesTypes(): array
    {
        return [];
    }
}
