<?php


namespace App\Service;

use PDO;

class SourceCustomerReader
{
    public function __construct(
        private readonly SourceConnection $sourceConnection,
    )
    {
    }

    /**
     * Récupère les commandes modifiées depuis une date donnée.
     *
     * @return array<int, array<string, mixed>>
     */
    public function readCustomers(
        string              $source,
        ?\DateTimeInterface $lastUpdate = null
    ): array
    {

        $pdo = $this->sourceConnection->connect($source);

        $config = $this->sourceConnection->getConfig($source);

        $prefix = $config['prefix'];

        $sql = $this->getCustomerQuery($source, $prefix);

        $statement = $pdo->prepare($sql);
        $lastUpdate =  $lastUpdate?->format('Y-m-d H:i:s')
            ?? '1970-01-01 00:00:00';

        $statement->execute([
            'lastUpdate' => $lastUpdate,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }


    private function getCustomerQuery(
        string $source,
        string $prefix
    ): string {
        return match ($source) {
            'anyskin' => sprintf(
                '
            SELECT
                c.id_customer,
                c.company,
                c.firstname,
                c.lastname,
                c.email,
                NULL AS siret,
                c.date_upd AS customer_date_upd,

                a.address1,
                a.address2,
                a.phone,
                a.phone_mobile,
                a.vat_number,
                a.postcode,
                a.city,

                cl.name AS country

            FROM %scustomer c

            LEFT JOIN %saddress a
                ON a.id_address = (
                    SELECT MAX(a2.id_address)
                    FROM %saddress a2
                    WHERE a2.id_customer = c.id_customer
                      AND a2.deleted = 0
                )

            LEFT JOIN %scountry_lang cl
                ON cl.id_country = a.id_country
                AND cl.id_lang = c.id_lang

            WHERE c.date_upd > :lastUpdate
            ORDER BY c.date_upd ASC
            ',
                $prefix,
                $prefix,
                $prefix,
                $prefix
            ),

            default => sprintf(
                '
            SELECT
                c.id_customer,
                c.company,
                c.firstname,
                c.lastname,
                c.email,
                NULL AS siret,
                c.date_upd AS customer_date_upd,

                a.address1,
                a.address2,
                a.phone,
                a.phone_mobile,
                a.vat_number,
                a.postcode,
                a.city,

                cl.name AS country

            FROM %scustomer c

            LEFT JOIN %saddress a
                ON a.id_address = (
                    SELECT MAX(a2.id_address)
                    FROM %saddress a2
                    WHERE a2.id_customer = c.id_customer
                      AND a2.deleted = 0
                )

            LEFT JOIN %scountry_lang cl
                ON cl.id_country = a.id_country
                AND cl.id_lang = c.id_lang

            WHERE c.date_upd > :lastUpdate
            ORDER BY c.date_upd ASC
            ',
                $prefix,
                $prefix,
                $prefix,
                $prefix
            ),
        };
    }
}
