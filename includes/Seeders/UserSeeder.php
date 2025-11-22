<?php

namespace MCDS\Seeders;

use MCDS\AbstractSeeder;

/**
 * WordPress Users Seeder
 */
class UserSeeder extends AbstractSeeder {

    /**
     * Sample addresses from around the world (matching MemberCore i18n data)
     * Over 150 locations for comprehensive map testing
     */
    private $addresses = [
        // NORTH AMERICA - United States (uses 2-letter state codes)
        ['street1' => '123 Main Street', 'street2' => 'Apt 4B', 'city' => 'New York', 'state' => 'NY', 'zip' => '10001', 'country' => 'US'],
        ['street1' => '456 Oak Avenue', 'street2' => '', 'city' => 'Los Angeles', 'state' => 'CA', 'zip' => '90001', 'country' => 'US'],
        ['street1' => '789 Pine Road', 'street2' => 'Suite 200', 'city' => 'Chicago', 'state' => 'IL', 'zip' => '60601', 'country' => 'US'],
        ['street1' => '321 Elm Street', 'street2' => '', 'city' => 'Houston', 'state' => 'TX', 'zip' => '77001', 'country' => 'US'],
        ['street1' => '654 Maple Drive', 'street2' => 'Unit 5', 'city' => 'Phoenix', 'state' => 'AZ', 'zip' => '85001', 'country' => 'US'],
        ['street1' => '890 Market Street', 'street2' => '', 'city' => 'San Francisco', 'state' => 'CA', 'zip' => '94102', 'country' => 'US'],
        ['street1' => '123 Peachtree St', 'street2' => '', 'city' => 'Atlanta', 'state' => 'GA', 'zip' => '30303', 'country' => 'US'],
        ['street1' => '456 Michigan Ave', 'street2' => 'Floor 3', 'city' => 'Detroit', 'state' => 'MI', 'zip' => '48226', 'country' => 'US'],
        ['street1' => '789 South Beach Dr', 'street2' => '', 'city' => 'Miami', 'state' => 'FL', 'zip' => '33139', 'country' => 'US'],
        ['street1' => '234 Pike Place', 'street2' => '', 'city' => 'Seattle', 'state' => 'WA', 'zip' => '98101', 'country' => 'US'],
        ['street1' => '567 Congress Ave', 'street2' => 'Suite 500', 'city' => 'Austin', 'state' => 'TX', 'zip' => '78701', 'country' => 'US'],
        ['street1' => '890 Tremont Street', 'street2' => '', 'city' => 'Boston', 'state' => 'MA', 'zip' => '02108', 'country' => 'US'],
        ['street1' => '123 Broadway', 'street2' => '', 'city' => 'Nashville', 'state' => 'TN', 'zip' => '37201', 'country' => 'US'],
        ['street1' => '456 Pearl Street', 'street2' => '', 'city' => 'Denver', 'state' => 'CO', 'zip' => '80203', 'country' => 'US'],
        ['street1' => '789 Fremont Street', 'street2' => '', 'city' => 'Las Vegas', 'state' => 'NV', 'zip' => '89101', 'country' => 'US'],
        ['street1' => '234 SW Park Ave', 'street2' => '', 'city' => 'Portland', 'state' => 'OR', 'zip' => '97205', 'country' => 'US'],
        ['street1' => '567 Walnut Street', 'street2' => '', 'city' => 'Philadelphia', 'state' => 'PA', 'zip' => '19106', 'country' => 'US'],
        ['street1' => '890 Hennepin Ave', 'street2' => '', 'city' => 'Minneapolis', 'state' => 'MN', 'zip' => '55403', 'country' => 'US'],
        ['street1' => '123 Wacker Drive', 'street2' => '', 'city' => 'Charlotte', 'state' => 'NC', 'zip' => '28202', 'country' => 'US'],
        ['street1' => '456 State Street', 'street2' => '', 'city' => 'Salt Lake City', 'state' => 'UT', 'zip' => '84111', 'country' => 'US'],

        // NORTH AMERICA - Canada (uses 2-letter province codes)
        ['street1' => '1234 Yonge Street', 'street2' => '', 'city' => 'Toronto', 'state' => 'ON', 'zip' => 'M4W 1J7', 'country' => 'CA'],
        ['street1' => '567 Granville Street', 'street2' => 'Suite 800', 'city' => 'Vancouver', 'state' => 'BC', 'zip' => 'V6C 1T1', 'country' => 'CA'],
        ['street1' => '890 Rue Sainte-Catherine', 'street2' => '', 'city' => 'Montreal', 'state' => 'QC', 'zip' => 'H3B 1A1', 'country' => 'CA'],
        ['street1' => '234 8th Avenue SW', 'street2' => '', 'city' => 'Calgary', 'state' => 'AB', 'zip' => 'T2P 1B5', 'country' => 'CA'],
        ['street1' => '567 Bank Street', 'street2' => '', 'city' => 'Ottawa', 'state' => 'ON', 'zip' => 'K1P 5N8', 'country' => 'CA'],
        ['street1' => '890 Portage Avenue', 'street2' => '', 'city' => 'Winnipeg', 'state' => 'MB', 'zip' => 'R3C 0A5', 'country' => 'CA'],
        ['street1' => '123 Barrington Street', 'street2' => '', 'city' => 'Halifax', 'state' => 'NS', 'zip' => 'B3J 1Y5', 'country' => 'CA'],
        ['street1' => '456 Jasper Avenue', 'street2' => '', 'city' => 'Edmonton', 'state' => 'AB', 'zip' => 'T5J 1J5', 'country' => 'CA'],

        // NORTH AMERICA - Mexico (uses state codes)
        ['street1' => 'Avenida Reforma 222', 'street2' => '', 'city' => 'Mexico City', 'state' => 'CMX', 'zip' => '06600', 'country' => 'MX'],
        ['street1' => 'Calle Juárez 789', 'street2' => '', 'city' => 'Guadalajara', 'state' => 'JAL', 'zip' => '44100', 'country' => 'MX'],
        ['street1' => 'Avenida Constitución 456', 'street2' => '', 'city' => 'Monterrey', 'state' => 'NLE', 'zip' => '64000', 'country' => 'MX'],
        ['street1' => 'Boulevard Kukulcán 123', 'street2' => '', 'city' => 'Cancún', 'state' => 'ROO', 'zip' => '77500', 'country' => 'MX'],

        // SOUTH AMERICA - Brazil (uses 2-letter state codes)
        ['street1' => 'Avenida Paulista 1578', 'street2' => '', 'city' => 'São Paulo', 'state' => 'SP', 'zip' => '01310-200', 'country' => 'BR'],
        ['street1' => 'Rua da Praia 456', 'street2' => 'Sala 302', 'city' => 'Rio de Janeiro', 'state' => 'RJ', 'zip' => '20040-020', 'country' => 'BR'],
        ['street1' => 'Avenida Atlântica 789', 'street2' => '', 'city' => 'Salvador', 'state' => 'BA', 'zip' => '40140-050', 'country' => 'BR'],
        ['street1' => 'Avenida Boa Viagem 234', 'street2' => '', 'city' => 'Recife', 'state' => 'PE', 'zip' => '51020-000', 'country' => 'BR'],
        ['street1' => 'Rua XV de Novembro 567', 'street2' => '', 'city' => 'Curitiba', 'state' => 'PR', 'zip' => '80020-310', 'country' => 'BR'],
        ['street1' => 'Avenida Afonso Pena 890', 'street2' => '', 'city' => 'Belo Horizonte', 'state' => 'MG', 'zip' => '30130-002', 'country' => 'BR'],

        // SOUTH AMERICA - Argentina
        ['street1' => 'Avenida Corrientes 1234', 'street2' => '', 'city' => 'Buenos Aires', 'state' => 'B', 'zip' => 'C1043', 'country' => 'AR'],
        ['street1' => 'Calle San Martín 567', 'street2' => '', 'city' => 'Córdoba', 'state' => 'X', 'zip' => 'X5000', 'country' => 'AR'],

        // SOUTH AMERICA - Chile
        ['street1' => 'Avenida Providencia 789', 'street2' => '', 'city' => 'Santiago', 'state' => 'RM', 'zip' => '7500000', 'country' => 'CL'],
        ['street1' => 'Calle Esmeralda 234', 'street2' => '', 'city' => 'Valparaíso', 'state' => 'VS', 'zip' => '2340000', 'country' => 'CL'],

        // SOUTH AMERICA - Colombia
        ['street1' => 'Carrera 7 #123-45', 'street2' => '', 'city' => 'Bogotá', 'state' => 'DC', 'zip' => '110111', 'country' => 'CO'],
        ['street1' => 'Calle 10 #67-89', 'street2' => '', 'city' => 'Medellín', 'state' => 'ANT', 'zip' => '050012', 'country' => 'CO'],

        // EUROPE - United Kingdom (no state field)
        ['street1' => '42 Baker Street', 'street2' => '', 'city' => 'London', 'state' => '', 'zip' => 'NW1 6XE', 'country' => 'GB'],
        ['street1' => '15 Abbey Road', 'street2' => 'Flat 3', 'city' => 'Liverpool', 'state' => '', 'zip' => 'L3 4AA', 'country' => 'GB'],
        ['street1' => '88 High Street', 'street2' => '', 'city' => 'Manchester', 'state' => '', 'zip' => 'M1 1AD', 'country' => 'GB'],
        ['street1' => '234 Princes Street', 'street2' => '', 'city' => 'Edinburgh', 'state' => '', 'zip' => 'EH2 4BJ', 'country' => 'GB'],
        ['street1' => '567 Queen Street', 'street2' => '', 'city' => 'Cardiff', 'state' => '', 'zip' => 'CF10 2BX', 'country' => 'GB'],
        ['street1' => '890 Royal Avenue', 'street2' => '', 'city' => 'Belfast', 'state' => '', 'zip' => 'BT1 1DA', 'country' => 'GB'],
        ['street1' => '123 Union Street', 'street2' => '', 'city' => 'Birmingham', 'state' => '', 'zip' => 'B2 4AA', 'country' => 'GB'],

        // EUROPE - Germany (no state field)
        ['street1' => 'Hauptstraße 123', 'street2' => '', 'city' => 'Berlin', 'state' => '', 'zip' => '10115', 'country' => 'DE'],
        ['street1' => 'Maximilianstraße 45', 'street2' => '', 'city' => 'Munich', 'state' => '', 'zip' => '80539', 'country' => 'DE'],
        ['street1' => 'Zeil 67', 'street2' => '', 'city' => 'Frankfurt', 'state' => '', 'zip' => '60313', 'country' => 'DE'],
        ['street1' => 'Mönckebergstraße 89', 'street2' => '', 'city' => 'Hamburg', 'state' => '', 'zip' => '20095', 'country' => 'DE'],
        ['street1' => 'Königsallee 12', 'street2' => '', 'city' => 'Düsseldorf', 'state' => '', 'zip' => '40212', 'country' => 'DE'],

        // EUROPE - France (no state field)
        ['street1' => '123 Avenue des Champs-Élysées', 'street2' => '', 'city' => 'Paris', 'state' => '', 'zip' => '75008', 'country' => 'FR'],
        ['street1' => '45 Rue de la République', 'street2' => 'Apt 3', 'city' => 'Lyon', 'state' => '', 'zip' => '69002', 'country' => 'FR'],
        ['street1' => '67 La Canebière', 'street2' => '', 'city' => 'Marseille', 'state' => '', 'zip' => '13001', 'country' => 'FR'],
        ['street1' => '89 Place de la Bourse', 'street2' => '', 'city' => 'Bordeaux', 'state' => '', 'zip' => '33000', 'country' => 'FR'],
        ['street1' => '234 Rue Alsace-Lorraine', 'street2' => '', 'city' => 'Toulouse', 'state' => '', 'zip' => '31000', 'country' => 'FR'],

        // EUROPE - Spain (uses province codes)
        ['street1' => 'Calle Gran Vía 28', 'street2' => '', 'city' => 'Madrid', 'state' => 'M', 'zip' => '28013', 'country' => 'ES'],
        ['street1' => 'Passeig de Gràcia 92', 'street2' => '', 'city' => 'Barcelona', 'state' => 'B', 'zip' => '08008', 'country' => 'ES'],
        ['street1' => 'Calle Larios 34', 'street2' => '', 'city' => 'Málaga', 'state' => 'MA', 'zip' => '29015', 'country' => 'ES'],
        ['street1' => 'Calle Colón 56', 'street2' => '', 'city' => 'Valencia', 'state' => 'V', 'zip' => '46004', 'country' => 'ES'],
        ['street1' => 'Plaza Nueva 78', 'street2' => '', 'city' => 'Seville', 'state' => 'SE', 'zip' => '41001', 'country' => 'ES'],

        // EUROPE - Italy (uses province codes)
        ['street1' => 'Via Roma 100', 'street2' => '', 'city' => 'Rome', 'state' => 'RM', 'zip' => '00184', 'country' => 'IT'],
        ['street1' => 'Via Dante 45', 'street2' => '', 'city' => 'Milan', 'state' => 'MI', 'zip' => '20121', 'country' => 'IT'],
        ['street1' => 'Via Torino 67', 'street2' => '', 'city' => 'Turin', 'state' => 'TO', 'zip' => '10121', 'country' => 'IT'],
        ['street1' => 'Via Garibaldi 89', 'street2' => '', 'city' => 'Naples', 'state' => 'NA', 'zip' => '80142', 'country' => 'IT'],
        ['street1' => 'Piazza San Marco 123', 'street2' => '', 'city' => 'Venice', 'state' => 'VE', 'zip' => '30124', 'country' => 'IT'],

        // EUROPE - Netherlands (no state field)
        ['street1' => 'Damrak 123', 'street2' => '', 'city' => 'Amsterdam', 'state' => '', 'zip' => '1012 LP', 'country' => 'NL'],
        ['street1' => 'Coolsingel 45', 'street2' => '', 'city' => 'Rotterdam', 'state' => '', 'zip' => '3011 AD', 'country' => 'NL'],
        ['street1' => 'Vredenburg 67', 'street2' => '', 'city' => 'Utrecht', 'state' => '', 'zip' => '3511 BD', 'country' => 'NL'],

        // EUROPE - Switzerland
        ['street1' => 'Bahnhofstrasse 89', 'street2' => '', 'city' => 'Zurich', 'state' => 'ZH', 'zip' => '8001', 'country' => 'CH'],
        ['street1' => 'Rue du Rhône 234', 'street2' => '', 'city' => 'Geneva', 'state' => 'GE', 'zip' => '1204', 'country' => 'CH'],

        // EUROPE - Belgium (no state field)
        ['street1' => 'Avenue Louise 123', 'street2' => '', 'city' => 'Brussels', 'state' => '', 'zip' => '1050', 'country' => 'BE'],

        // EUROPE - Austria (no state field)
        ['street1' => 'Kärntner Straße 45', 'street2' => '', 'city' => 'Vienna', 'state' => '', 'zip' => '1010', 'country' => 'AT'],

        // EUROPE - Sweden
        ['street1' => 'Drottninggatan 67', 'street2' => '', 'city' => 'Stockholm', 'state' => 'AB', 'zip' => '111 60', 'country' => 'SE'],

        // EUROPE - Norway (no state field)
        ['street1' => 'Karl Johans gate 89', 'street2' => '', 'city' => 'Oslo', 'state' => '', 'zip' => '0154', 'country' => 'NO'],

        // EUROPE - Denmark (no state field)
        ['street1' => 'Strøget 123', 'street2' => '', 'city' => 'Copenhagen', 'state' => '', 'zip' => '1160', 'country' => 'DK'],

        // EUROPE - Poland (no state field)
        ['street1' => 'Marszałkowska 234', 'street2' => '', 'city' => 'Warsaw', 'state' => '', 'zip' => '00-001', 'country' => 'PL'],

        // ASIA - Japan
        ['street1' => '1-1-1 Chiyoda', 'street2' => '', 'city' => 'Tokyo', 'state' => '13', 'zip' => '100-0001', 'country' => 'JP'],
        ['street1' => '2-2-2 Namba', 'street2' => '', 'city' => 'Osaka', 'state' => '27', 'zip' => '542-0076', 'country' => 'JP'],
        ['street1' => '3-3-3 Sakae', 'street2' => '', 'city' => 'Nagoya', 'state' => '23', 'zip' => '460-0008', 'country' => 'JP'],
        ['street1' => '4-4-4 Tenjin', 'street2' => '', 'city' => 'Fukuoka', 'state' => '40', 'zip' => '810-0001', 'country' => 'JP'],

        // ASIA - China
        ['street1' => '123 Nanjing Road', 'street2' => '', 'city' => 'Shanghai', 'state' => 'SH', 'zip' => '200001', 'country' => 'CN'],
        ['street1' => '456 Wangfujing Street', 'street2' => '', 'city' => 'Beijing', 'state' => 'BJ', 'zip' => '100006', 'country' => 'CN'],
        ['street1' => '789 Huaqiangbei', 'street2' => '', 'city' => 'Shenzhen', 'state' => 'GD', 'zip' => '518031', 'country' => 'CN'],
        ['street1' => '234 Tianhe Road', 'street2' => '', 'city' => 'Guangzhou', 'state' => 'GD', 'zip' => '510630', 'country' => 'CN'],

        // ASIA - India (uses state codes)
        ['street1' => '123 MG Road', 'street2' => '', 'city' => 'Mumbai', 'state' => 'MH', 'zip' => '400001', 'country' => 'IN'],
        ['street1' => '456 Connaught Place', 'street2' => '', 'city' => 'New Delhi', 'state' => 'DL', 'zip' => '110001', 'country' => 'IN'],
        ['street1' => '789 Brigade Road', 'street2' => '', 'city' => 'Bangalore', 'state' => 'KA', 'zip' => '560001', 'country' => 'IN'],
        ['street1' => '234 Mount Road', 'street2' => '', 'city' => 'Chennai', 'state' => 'TN', 'zip' => '600002', 'country' => 'IN'],
        ['street1' => '567 Park Street', 'street2' => '', 'city' => 'Kolkata', 'state' => 'WB', 'zip' => '700016', 'country' => 'IN'],

        // ASIA - South Korea
        ['street1' => '123 Gangnam-daero', 'street2' => '', 'city' => 'Seoul', 'state' => '', 'zip' => '06236', 'country' => 'KR'],
        ['street1' => '456 Haeundae Beach', 'street2' => '', 'city' => 'Busan', 'state' => '', 'zip' => '48094', 'country' => 'KR'],

        // ASIA - Singapore
        ['street1' => '1 Raffles Place', 'street2' => '#20-01', 'city' => 'Singapore', 'state' => '', 'zip' => '048616', 'country' => 'SG'],

        // ASIA - Thailand
        ['street1' => '123 Sukhumvit Road', 'street2' => '', 'city' => 'Bangkok', 'state' => '10', 'zip' => '10110', 'country' => 'TH'],

        // ASIA - Malaysia
        ['street1' => '456 Jalan Ampang', 'street2' => '', 'city' => 'Kuala Lumpur', 'state' => '14', 'zip' => '50450', 'country' => 'MY'],

        // ASIA - Indonesia
        ['street1' => '789 Jalan Sudirman', 'street2' => '', 'city' => 'Jakarta', 'state' => 'JK', 'zip' => '12190', 'country' => 'ID'],

        // ASIA - Philippines
        ['street1' => '123 Makati Avenue', 'street2' => '', 'city' => 'Manila', 'state' => 'MNL', 'zip' => '1200', 'country' => 'PH'],

        // ASIA - Vietnam
        ['street1' => '456 Nguyen Hue', 'street2' => '', 'city' => 'Ho Chi Minh City', 'state' => 'SG', 'zip' => '700000', 'country' => 'VN'],

        // MIDDLE EAST - United Arab Emirates
        ['street1' => 'Sheikh Zayed Road 123', 'street2' => '', 'city' => 'Dubai', 'state' => '', 'zip' => '00000', 'country' => 'AE'],
        ['street1' => 'Corniche Road 456', 'street2' => '', 'city' => 'Abu Dhabi', 'state' => '', 'zip' => '00000', 'country' => 'AE'],

        // MIDDLE EAST - Israel
        ['street1' => 'Rothschild Boulevard 789', 'street2' => '', 'city' => 'Tel Aviv', 'state' => '', 'zip' => '6688218', 'country' => 'IL'],

        // MIDDLE EAST - Turkey
        ['street1' => 'İstiklal Caddesi 234', 'street2' => '', 'city' => 'Istanbul', 'state' => '34', 'zip' => '34433', 'country' => 'TR'],

        // OCEANIA - Australia (uses 2-3 letter state codes)
        ['street1' => '100 George Street', 'street2' => '', 'city' => 'Sydney', 'state' => 'NSW', 'zip' => '2000', 'country' => 'AU'],
        ['street1' => '250 Collins Street', 'street2' => 'Level 12', 'city' => 'Melbourne', 'state' => 'VIC', 'zip' => '3000', 'country' => 'AU'],
        ['street1' => '123 Adelaide Street', 'street2' => '', 'city' => 'Brisbane', 'state' => 'QLD', 'zip' => '4000', 'country' => 'AU'],
        ['street1' => '456 Murray Street', 'street2' => '', 'city' => 'Perth', 'state' => 'WA', 'zip' => '6000', 'country' => 'AU'],
        ['street1' => '789 Rundle Mall', 'street2' => '', 'city' => 'Adelaide', 'state' => 'SA', 'zip' => '5000', 'country' => 'AU'],
        ['street1' => '234 Elizabeth Street', 'street2' => '', 'city' => 'Hobart', 'state' => 'TAS', 'zip' => '7000', 'country' => 'AU'],

        // OCEANIA - New Zealand
        ['street1' => '123 Queen Street', 'street2' => '', 'city' => 'Auckland', 'state' => 'AUK', 'zip' => '1010', 'country' => 'NZ'],
        ['street1' => '456 Lambton Quay', 'street2' => '', 'city' => 'Wellington', 'state' => 'WGN', 'zip' => '6011', 'country' => 'NZ'],

        // AFRICA - South Africa
        ['street1' => '123 Long Street', 'street2' => '', 'city' => 'Cape Town', 'state' => 'WC', 'zip' => '8001', 'country' => 'ZA'],
        ['street1' => '456 Sandton Drive', 'street2' => '', 'city' => 'Johannesburg', 'state' => 'GT', 'zip' => '2196', 'country' => 'ZA'],

        // AFRICA - Egypt
        ['street1' => '789 Tahrir Square', 'street2' => '', 'city' => 'Cairo', 'state' => 'C', 'zip' => '11511', 'country' => 'EG'],

        // AFRICA - Nigeria
        ['street1' => '234 Victoria Island', 'street2' => '', 'city' => 'Lagos', 'state' => 'LA', 'zip' => '101241', 'country' => 'NG'],

        // AFRICA - Kenya
        ['street1' => '567 Kenyatta Avenue', 'street2' => '', 'city' => 'Nairobi', 'state' => '', 'zip' => '00100', 'country' => 'KE'],
    ];

    /**
     * First names from various cultures
     */
    private $male_first_names = [
        'James', 'John', 'Robert', 'Michael', 'William', 'David', 'Richard', 'Joseph',
        'Thomas', 'Charles', 'Christopher', 'Daniel', 'Matthew', 'Anthony', 'Donald', 'Mark',
        'Paul', 'Steven', 'Andrew', 'Kenneth', 'Joshua', 'Kevin', 'Brian', 'George',
        'Edward', 'Ronald', 'Timothy', 'Jason', 'Jeffrey', 'Ryan', 'Jacob', 'Gary',
        'Liam', 'Noah', 'Oliver', 'Elijah', 'Lucas', 'Mason', 'Logan', 'Alexander',
        'Ethan', 'Benjamin', 'Henry', 'Sebastian', 'Jack', 'Owen', 'Carter', 'Wyatt',
        'Muhammad', 'Ali', 'Omar', 'Hassan', 'Ahmed', 'Yuki', 'Haruto', 'Hans',
        'Pierre', 'Carlos', 'Marco', 'Ivan', 'Wei', 'Diego', 'Santiago', 'Luis',
        'Andre', 'Felix', 'Hugo', 'Leon', 'Max', 'Kai'
    ];

    private $female_first_names = [
        'Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara', 'Susan', 'Jessica',
        'Sarah', 'Karen', 'Nancy', 'Lisa', 'Betty', 'Margaret', 'Sandra', 'Ashley',
        'Kimberly', 'Emily', 'Donna', 'Michelle', 'Carol', 'Amanda', 'Dorothy', 'Melissa',
        'Deborah', 'Stephanie', 'Rebecca', 'Sharon', 'Laura', 'Cynthia', 'Kathleen', 'Amy',
        'Olivia', 'Emma', 'Ava', 'Sophia', 'Isabella', 'Mia', 'Charlotte', 'Amelia',
        'Harper', 'Evelyn', 'Abigail', 'Emily', 'Ella', 'Scarlett', 'Grace', 'Chloe',
        'Fatima', 'Aisha', 'Zara', 'Layla', 'Sakura', 'Mei', 'Yuki', 'Anna',
        'Marie', 'Sofia', 'Giulia', 'Anastasia', 'Ling', 'Camila', 'Valentina', 'Isabella',
        'Elena', 'Luna', 'Nina', 'Zoe', 'Maya', 'Lily'
    ];

    /**
     * Last names from various cultures
     */
    private $last_names = [
        // English/American
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
        'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas',
        'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White', 'Harris',
        'Clark', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright',
        'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green', 'Adams', 'Nelson',
        'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts', 'Turner',
        'Phillips', 'Evans', 'Edwards', 'Collins', 'Stewart', 'Morris', 'Reed', 'Cook',
        // German
        'Müller', 'Schmidt', 'Schneider', 'Fischer', 'Weber', 'Meyer', 'Wagner', 'Becker',
        'Schulz', 'Hoffmann', 'Schäfer', 'Koch', 'Bauer', 'Richter', 'Klein', 'Wolf',
        // French
        'Dubois', 'Dupont', 'Bernard', 'Petit', 'Robert', 'Richard', 'Durand', 'Leroy',
        'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand',
        // Italian
        'Rossi', 'Russo', 'Ferrari', 'Esposito', 'Bianchi', 'Romano', 'Colombo', 'Ricci',
        'Marino', 'Greco', 'Bruno', 'Gallo', 'Conti', 'De Luca', 'Costa', 'Giordano',
        // Japanese
        'Yamamoto', 'Tanaka', 'Suzuki', 'Watanabe', 'Sato', 'Kobayashi', 'Kato', 'Yoshida',
        'Nakamura', 'Ito', 'Sasaki', 'Yamada', 'Takahashi', 'Mori', 'Abe', 'Ikeda',
        // Portuguese/Brazilian
        'Silva', 'Santos', 'Oliveira', 'Souza', 'Costa', 'Ferreira', 'Rodrigues', 'Alves',
        'Pereira', 'Lima', 'Gomes', 'Ribeiro', 'Martins', 'Carvalho', 'Rocha', 'Almeida',
        // Indian
        'Kumar', 'Sharma', 'Patel', 'Singh', 'Khan', 'Ali', 'Ahmed', 'Hassan',
        'Gupta', 'Reddy', 'Verma', 'Joshi', 'Agarwal', 'Desai', 'Rao', 'Nair'
    ];

    /**
     * Initialize seeder
     */
    protected function init() {
        $this->key = 'wp_users';
        $this->name = __('WordPress Users', 'membercore-data-seeder');
        $this->description = __('Creates WordPress users with random usernames, names, emails, bios, and all default WP user fields. If MemberCore is active, also populates address fields with addresses from around the world. If MemberCore Directory is active, assigns profile avatars to users.', 'membercore-data-seeder');
        $this->default_count = 50;
        $this->default_batch_size = 200;

        // Add custom settings fields
        $this->settings_fields = [
            [
                'key' => 'user_role',
                'label' => __('User Role', 'membercore-data-seeder'),
                'type' => 'select',
                'default' => 'subscriber',
                'options' => [
                    'subscriber' => __('Subscriber', 'membercore-data-seeder'),
                    'contributor' => __('Contributor', 'membercore-data-seeder'),
                    'author' => __('Author', 'membercore-data-seeder')
                ],
                'required' => true
            ]
        ];
    }

    /**
     * Get list of seeders that depend on users
     * Subscriptions depend on users, so resetting users should also reset subscriptions
     */
    public function get_dependents() {
        return ['membercore_subscriptions'];
    }

    /**
     * Check if MemberCore is active
     */
    private function is_membercore_active() {
        return class_exists('MecoUser');
    }

    /**
     * Generate random username (checks for uniqueness)
     */
    private function generate_unique_username($first_name, $last_name) {
        global $wpdb;

        // Try username variations until we find a unique one
        $base_username = strtolower($first_name . $last_name);
        $username = sanitize_user($base_username, true);

        // If username exists, add random numbers
        $attempt = 0;
        while (username_exists($username) && $attempt < 10) {
            $username = $base_username . rand(100, 9999);
            $attempt++;
        }

        // Last resort: add timestamp
        if (username_exists($username)) {
            $username = $base_username . time();
        }

        return $username;
    }

    /**
     * Generate random bio
     */
    private function generate_bio() {
        $bios = [
            "Passionate about technology and innovation. Coffee enthusiast.",
            "Digital nomad exploring the world one city at a time.",
            "Lover of good books, great coffee, and beautiful sunsets.",
            "Entrepreneur, developer, and lifelong learner.",
            "Creating meaningful experiences through design and code.",
            "Adventure seeker and storyteller. Always curious.",
            "Building products that people love to use.",
            "Focused on making a positive impact in the world.",
            "Creative thinker with a passion for solving problems.",
            "Nature lover and outdoor enthusiast.",
            "Music, art, and everything in between.",
            "Foodie, traveler, and amateur photographer.",
            "Dedicated to continuous learning and growth.",
            "Believer in the power of community and collaboration.",
            "Striving to make every day count.",
        ];

        return $bios[array_rand($bios)];
    }

    /**
     * Run a batch of seeding
     */
    public function seed_batch($offset, $limit, $settings) {
        global $wpdb;

        $user_role = !empty($settings['user_role']) ? $settings['user_role'] : 'subscriber';
        $membercore_active = $this->is_membercore_active();

        // Prepare bulk inserts - generate all data first
        $user_data = [];
        $time = current_time('mysql');

        // Use a single pre-hashed password for all seeded users for speed
        // This is fine for test data since these are not real users
        static $hashed_password = null;
        if ($hashed_password === null) {
            $hashed_password = wp_hash_password('password123');
        }

        for ($i = 0; $i < $limit; $i++) {
            // Check if seeder has been cancelled
            if ($this->is_cancelled()) {
                // Return early with the number of users processed so far
                return [
                    'processed' => $i,
                    'cancelled' => true
                ];
            }

            // Randomly choose gender and generate matching name
            $gender = (rand(0, 1) === 0) ? 'male' : 'female';
            $first_name = ($gender === 'male')
                ? $this->male_first_names[array_rand($this->male_first_names)]
                : $this->female_first_names[array_rand($this->female_first_names)];
            $last_name = $this->last_names[array_rand($this->last_names)];
            $username = $this->generate_unique_username($first_name, $last_name);
            $user_nicename = sanitize_title($username);
            $email = $username . '@example.com';
            $display_name = $first_name . ' ' . $last_name;
            $bio = $this->generate_bio();

            $user_data[] = [
                'username' => $username,
                'user_nicename' => $user_nicename,
                'password' => $hashed_password,
                'email' => $email,
                'display_name' => $display_name,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'bio' => $bio,
                'gender' => $gender
            ];
        }

        // Prepare user insert values
        $user_values = [];
        foreach ($user_data as $data) {
            $user_values[] = $wpdb->prepare(
                "(%s, %s, %s, %s, %s, %s, %s)",
                $data['username'],
                $data['password'],
                $data['user_nicename'],
                $data['email'],
                '',
                $time,
                $data['display_name']
            );
        }

        // Insert all users at once
        if (!empty($user_values)) {
            $sql = "INSERT INTO {$wpdb->users}
                    (user_login, user_pass, user_nicename, user_email, user_url, user_registered, display_name)
                    VALUES " . implode(', ', $user_values);

            $result = $wpdb->query($sql);

            if ($result === false) {
                return [
                    'processed' => 0,
                    'error' => $wpdb->last_error ?: __('Failed to insert users', 'membercore-data-seeder')
                ];
            }

            // Get the IDs of inserted users
            $first_user_id = $wpdb->insert_id;
            $user_ids = range($first_user_id, $first_user_id + $limit - 1);

            // Prepare usermeta bulk insert
            $meta_values = [];
            foreach ($user_ids as $idx => $user_id) {
                $data = $user_data[$idx];

                // Capabilities for role
                $capabilities = serialize([$user_role => true]);

                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, $wpdb->prefix . 'capabilities', $capabilities);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, $wpdb->prefix . 'user_level', '0');
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, 'first_name', $data['first_name']);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, 'last_name', $data['last_name']);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, 'nickname', $data['username']);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, 'description', $data['bio']);
                $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, '_mcds_seeder_key', $this->key);

                // If MemberCore is active, add address fields
                if ($membercore_active) {
                    $address = $this->addresses[array_rand($this->addresses)];

                    $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, 'meco-address-one', $address['street1']);
                    $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, 'meco-address-two', $address['street2']);
                    $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, 'meco-address-city', $address['city']);
                    $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, 'meco-address-state', $address['state']);
                    $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, 'meco-address-zip', $address['zip']);
                    $meta_values[] = $wpdb->prepare("(%d, %s, %s)", $user_id, 'meco-address-country', $address['country']);
                }
            }

            // Insert all usermeta at once
            if (!empty($meta_values)) {
                $sql = "INSERT INTO {$wpdb->usermeta} (user_id, meta_key, meta_value) VALUES " . implode(', ', $meta_values);
                $wpdb->query($sql);
            }

            // Add profile photos if membercore-directory is active
            $this->add_profile_photos($user_ids, $user_data);

            return ['processed' => $limit];
        }

        return ['processed' => 0];
    }

    /**
     * Add profile photos for seeded users (membercore-directory integration)
     */
    private function add_profile_photos($user_ids, $user_data) {
        global $wpdb;

        // Get base avatar directory
        $avatar_base_dir = MCDS_PLUGIN_DIR . 'assets/images/avatars/';

        // Get gender-specific avatar files
        $male_avatars = glob($avatar_base_dir . 'male/male-*.jpg');
        $female_avatars = glob($avatar_base_dir . 'female/female-*.jpg');

        if (empty($male_avatars) && empty($female_avatars)) {
            return; // No avatars available
        }

        // Check if membercore-directory table exists
        $mcdir_table = $wpdb->prefix . 'mcdir_profile_images';
        $mcdir_active = $wpdb->get_var("SHOW TABLES LIKE '{$mcdir_table}'") === $mcdir_table;

        if (!$mcdir_active) {
            return; // membercore-directory not installed
        }

        // Get WordPress uploads directory
        $upload_dir = wp_upload_dir();
        $profile_images_values = [];
        $current_time = current_time('mysql');
        $timestamp = time();

        foreach ($user_ids as $index => $user_id) {
            // Get gender for this user
            $gender = $user_data[$index]['gender'];

            // Select avatar based on gender
            if ($gender === 'male' && !empty($male_avatars)) {
                $source_file = $male_avatars[array_rand($male_avatars)];
            } elseif ($gender === 'female' && !empty($female_avatars)) {
                $source_file = $female_avatars[array_rand($female_avatars)];
            } else {
                // Fallback: use any available avatar
                $all_avatars = array_merge($male_avatars, $female_avatars);
                if (empty($all_avatars)) continue;
                $source_file = $all_avatars[array_rand($all_avatars)];
            }

            $filename = 'avatar-' . $user_id . '-' . $timestamp . '.jpg';
            $dest_file = $upload_dir['path'] . '/' . $filename;

            // Copy avatar to uploads directory
            if (copy($source_file, $dest_file)) {
                $file_url = $upload_dir['url'] . '/' . $filename;
                $file_size = filesize($dest_file);

                $profile_images_values[] = $wpdb->prepare(
                    "(%d, %s, %s, %s, %d, %s, %s)",
                    $user_id,
                    $file_url,
                    'avatar',
                    'approved',
                    $file_size,
                    $current_time,
                    $current_time
                );
            }
        }

        // Bulk insert profile images
        if (!empty($profile_images_values)) {
            $sql = "INSERT INTO {$mcdir_table} (user_id, url, type, status, size, created_at, updated_at)
                    VALUES " . implode(', ', $profile_images_values);
            $wpdb->query($sql);
        }
    }

    /**
     * Delete profile photos for seeded users (membercore-directory integration)
     */
    private function delete_profile_photos($user_ids) {
        global $wpdb;

        if (empty($user_ids)) {
            return;
        }

        // Check if membercore-directory table exists
        $mcdir_table = $wpdb->prefix . 'mcdir_profile_images';
        $mcdir_active = $wpdb->get_var("SHOW TABLES LIKE '{$mcdir_table}'") === $mcdir_table;

        if (!$mcdir_active) {
            return;
        }

        $ids_placeholder = implode(',', array_fill(0, count($user_ids), '%d'));

        // Get profile image URLs before deleting records
        $profile_images = $wpdb->get_results($wpdb->prepare(
            "SELECT url FROM {$mcdir_table} WHERE user_id IN ($ids_placeholder)",
            ...$user_ids
        ));

        // Delete the actual image files from uploads directory
        $upload_dir = wp_upload_dir();
        foreach ($profile_images as $image) {
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image->url);
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Delete profile image records
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$mcdir_table} WHERE user_id IN ($ids_placeholder)",
            ...$user_ids
        ));
    }

    /**
     * Reset/clear all data created by this seeder
     */
    /**
     * Get count of items to reset (for progress tracking)
     */
    public function get_reset_count() {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta}
             WHERE meta_key = %s AND meta_value = %s AND user_id > 1",
            '_mcds_seeder_key',
            $this->key
        ));

        return intval($count);
    }

    /**
     * Reset/clear data in batches
     *
     * @param int $offset Batch offset (user number to start from)
     * @param int $limit Batch size (number of users to process)
     * @return array Results with 'processed' count
     */
    public function reset_batch($offset, $limit) {
        global $wpdb;

        // Get batch of user IDs (always use offset 0 since we're deleting as we go)
        $user_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = %s AND meta_value = %s AND user_id > 1
             ORDER BY user_id
             LIMIT %d",
            '_mcds_seeder_key',
            $this->key,
            $limit
        ));

        if (empty($user_ids)) {
            return ['processed' => 0];
        }

        $ids_placeholder = implode(',', array_fill(0, count($user_ids), '%d'));

        // Delete profile photos if membercore-directory is active
        $this->delete_profile_photos($user_ids);

        // Delete usermeta
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE user_id IN ($ids_placeholder)",
            ...$user_ids
        ));

        // Delete users
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->users} WHERE ID IN ($ids_placeholder)",
            ...$user_ids
        ));

        return ['processed' => count($user_ids)];
    }

    /**
     * Legacy reset method - now just calls reset_batch to process everything
     */
    public function reset() {
        $count = $this->get_reset_count();
        if ($count > 0) {
            $this->reset_batch(0, $count);
        }
    }
}
