<?php
require_once __DIR__ . '/includes/db.php';

$db = get_db();

$db->exec("DROP TABLE IF EXISTS appointments");
$db->exec("DROP TABLE IF EXISTS services");
$db->exec("DROP TABLE IF EXISTS admins");

$db->exec("CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE NOT NULL,
    name_pt TEXT NOT NULL,
    name_fr TEXT NOT NULL,
    desc_pt TEXT NOT NULL,
    desc_fr TEXT NOT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
)");

$db->exec("CREATE TABLE IF NOT EXISTS appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    nif TEXT,
    address TEXT,
    service_id INTEGER NOT NULL,
    preferred_date TEXT NOT NULL,
    preferred_time TEXT NOT NULL,
    notes TEXT,
    payment_reference TEXT UNIQUE NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    paid INTEGER NOT NULL DEFAULT 0,
    admin_notes TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT,
    FOREIGN KEY(service_id) REFERENCES services(id)
)");

$db->exec("CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now'))
)");

$services = [
    [
        'slug'       => 'procuracoes',
        'name_pt'    => 'Procurações',
        'name_fr'    => 'Procurations',
        'desc_pt'    => 'O solicitador actua como procurador em nome dos seus clientes, representando-os junto de entidades públicas e privadas em Portugal. Este serviço é especialmente útil para emigrantes e cidadãos portugueses residentes em França que não podem estar presentes pessoalmente para tratar de assuntos legais e administrativos. A procuração pode ser geral ou especial, conforme as necessidades do cliente. Tratamos de todo o processo de autenticação e legalização, garantindo que os documentos têm plena validade jurídica em Portugal e no estrangeiro. Contacte-nos para saber mais sobre como podemos representá-lo.',
        'desc_fr'    => "Le solicitor agit en tant que mandataire au nom de ses clients, les représentant auprès des entités publiques et privées au Portugal. Ce service est particulièrement utile pour les émigrants et les citoyens portugais résidant en France qui ne peuvent pas être présents personnellement pour gérer des affaires juridiques et administratives. La procuration peut être générale ou spéciale, selon les besoins du client. Nous gérons l'ensemble du processus d'authentification et de légalisation, garantissant que les documents ont pleine validité juridique au Portugal et à l'étranger. Contactez-nous pour en savoir plus.",
        'sort_order' => 1,
    ],
    [
        'slug'       => 'testamentos',
        'name_pt'    => 'Testamentos e Heranças',
        'name_fr'    => 'Testaments et Héritages',
        'desc_pt'    => "Garanta que a sua vontade é respeitada e que o seu património é transmitido de acordo com os seus desejos. Oferecemos serviços completos de elaboração de testamentos, aconselhamento sobre heranças e partilhas, e representação em processos de inventário. Para emigrantes portugueses em França, ajudamos a navegar pelas complexidades do direito sucessório luso-francês, assegurando que os seus bens em Portugal são devidamente tratados. Tratamos ainda do registo de testamentos e de toda a documentação necessária junto das autoridades competentes, proporcionando tranquilidade para si e para os seus familiares.",
        'desc_fr'    => "Assurez-vous que votre volonté est respectée et que votre patrimoine est transmis selon vos souhaits. Nous offrons des services complets de rédaction de testaments, de conseil en matière de successions et de partages, et de représentation dans les procédures d'inventaire. Pour les émigrés portugais en France, nous aidons à naviguer dans les complexités du droit successoral franco-portugais, en veillant à ce que vos biens au Portugal soient correctement gérés. Nous nous occupons également de l'enregistrement des testaments et de toute la documentation nécessaire auprès des autorités compétentes.",
        'sort_order' => 2,
    ],
    [
        'slug'       => 'imoveis',
        'name_pt'    => 'Compra e Venda de Imóveis',
        'name_fr'    => 'Achat et Vente Immobilière',
        'desc_pt'    => "Prestamos assistência completa em todas as fases da compra e venda de imóveis em Portugal. Desde a análise e verificação da documentação do imóvel, passando pela elaboração e revisão de contratos de promessa de compra e venda, até à escritura final. Para compradores e vendedores residentes no estrangeiro, oferecemos um serviço personalizado que inclui a representação por procuração, eliminando a necessidade de deslocação a Portugal. Verificamos toda a situação legal e fiscal do imóvel, identificamos eventuais ónus ou encargos, e asseguramos que a transação decorre de forma segura e transparente para todas as partes envolvidas.",
        'desc_fr'    => "Nous fournissons une assistance complète à toutes les étapes de l'achat et de la vente de biens immobiliers au Portugal. Depuis l'analyse et la vérification de la documentation du bien, en passant par la rédaction et la révision des contrats de promesse de vente, jusqu'à l'acte final. Pour les acheteurs et vendeurs résidant à l'étranger, nous offrons un service personnalisé incluant la représentation par procuration, éliminant la nécessité de se déplacer au Portugal. Nous vérifions toute la situation juridique et fiscale du bien, identifions les éventuelles charges ou hypothèques, et veillons à ce que la transaction se déroule de manière sûre et transparente.",
        'sort_order' => 3,
    ],
    [
        'slug'       => 'divorcio',
        'name_pt'    => 'Divórcio e Família',
        'name_fr'    => 'Divorce et Famille',
        'desc_pt'    => "O processo de divórcio pode ser emocionalmente difícil, mas com o apoio jurídico adequado torna-se mais simples e menos stressante. Oferecemos serviços de divórcio por mútuo consentimento e litigioso, tratando de toda a documentação e representação necessárias. Gerimos igualmente os processos de partilha de bens, regulação do poder paternal e pensões de alimentos. Para casais com bens ou residência em Portugal e em França, ajudamos a coordenar os procedimentos legais em ambos os países, garantindo que os seus direitos são plenamente protegidos durante todo o processo.",
        'desc_fr'    => "Le processus de divorce peut être émotionnellement difficile, mais avec le bon soutien juridique, il devient plus simple et moins stressant. Nous offrons des services de divorce par consentement mutuel et contentieux, gérant toute la documentation et la représentation nécessaires. Nous gérons également les procédures de partage de biens, la régulation de l'autorité parentale et les pensions alimentaires. Pour les couples ayant des biens ou une résidence au Portugal et en France, nous aidons à coordonner les procédures légales dans les deux pays.",
        'sort_order' => 4,
    ],
    [
        'slug'       => 'documentos',
        'name_pt'    => 'Reconhecimento de Documentos',
        'name_fr'    => 'Reconnaissance de Documents',
        'desc_pt'    => "O reconhecimento de documentos é um serviço fundamental para quem necessita de validar a autenticidade de assinaturas e documentos para uso em Portugal ou no estrangeiro. Procedemos ao reconhecimento de assinaturas, autenticação de fotocópias, tradução juramentada e apostilha de documentos conforme a Convenção de Haia. Para emigrantes portugueses em França, este serviço é essencial para validar documentos académicos, profissionais ou pessoais.",
        'desc_fr'    => "La reconnaissance de documents est un service fondamental pour ceux qui ont besoin de valider l'authenticité des signatures et des documents pour usage au Portugal ou à l'étranger. Nous procédons à la reconnaissance de signatures, à l'authentification de photocopies, à la traduction assermentée et à l'apostille de documents conformément à la Convention de La Haye.",
        'sort_order' => 5,
    ],
    [
        'slug'       => 'registo-civil',
        'name_pt'    => 'Registo Civil e Notariado',
        'name_fr'    => 'État Civil et Notariat',
        'desc_pt'    => "Prestamos serviços completos de registo civil e notariado, incluindo registo de nascimentos, casamentos, óbitos e outros atos civis junto das conservatórias portuguesas. Para emigrantes, auxiliamos no registo tardio de eventos ocorridos no estrangeiro, como nascimentos e casamentos celebrados em França. O nosso objetivo é simplificar todos estes processos burocráticos, permitindo que os nossos clientes resolvam os seus assuntos administrativos de forma eficiente e tranquila.",
        'desc_fr'    => "Nous fournissons des services complets d'état civil et de notariat, notamment l'enregistrement des naissances, mariages, décès et autres actes civils auprès des registres portugais. Pour les émigrés, nous aidons à l'enregistrement tardif d'événements survenus à l'étranger, tels que les naissances et mariages célébrés en France.",
        'sort_order' => 6,
    ],
    [
        'slug'       => 'aconselhamento',
        'name_pt'    => 'Aconselhamento Jurídico',
        'name_fr'    => 'Conseil Juridique',
        'desc_pt'    => "O aconselhamento jurídico é a base de qualquer decisão legal bem fundamentada. Oferecemos consultas personalizadas para esclarecer dúvidas sobre direito português, ajudando os nossos clientes a compreender os seus direitos e obrigações em diversas situações. As nossas consultas podem ser realizadas presencialmente ou à distância por videoconferência.",
        'desc_fr'    => "Le conseil juridique est la base de toute décision juridique bien fondée. Nous offrons des consultations personnalisées pour clarifier les questions sur le droit portugais, aidant nos clients à comprendre leurs droits et obligations dans diverses situations. Nos consultations peuvent être réalisées en personne ou à distance par vidéoconférence.",
        'sort_order' => 7,
    ],
    [
        'slug'       => 'emigrantes',
        'name_pt'    => 'Apoio a Emigrantes',
        'name_fr'    => 'Soutien aux Émigrés',
        'desc_pt'    => "Especializados no apoio a emigrantes portugueses e lusodescendentes residentes em França, oferecemos um conjunto abrangente de serviços adaptados às necessidades específicas desta comunidade. Desde a manutenção de documentação portuguesa atualizada até à gestão de bens imóveis em Portugal, passando pelo apoio em processos de regresso ao país. Compreendemos os desafios únicos enfrentados pelos emigrantes e estamos aqui para simplificar os aspectos legais e administrativos da vida entre dois países.",
        'desc_fr'    => "Spécialisés dans le soutien aux émigrés portugais et aux Luso-descendants résidant en France, nous offrons un ensemble complet de services adaptés aux besoins spécifiques de cette communauté. De la maintenance de la documentation portugaise à jour à la gestion de biens immobiliers au Portugal. Nous comprenons les défis uniques auxquels font face les émigrés.",
        'sort_order' => 8,
    ],
];

$stmt = $db->prepare("INSERT INTO services (slug, name_pt, name_fr, desc_pt, desc_fr, active, sort_order) VALUES (?, ?, ?, ?, ?, 1, ?)");
foreach ($services as $s) {
    $stmt->execute([$s['slug'], $s['name_pt'], $s['name_fr'], $s['desc_pt'], $s['desc_fr'], $s['sort_order']]);
}

$adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
$db->prepare("INSERT OR REPLACE INTO admins (username, password) VALUES (?, ?)")->execute(['admin', $adminPassword]);

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Install</title><style>body{font-family:sans-serif;max-width:600px;margin:60px auto;padding:0 20px;}h1{color:#1e3a5f;}</style></head><body>';
echo '<h1>&#x2705; Installation Complete</h1>';
echo '<p>Database created successfully with 8 services.</p>';
echo '<p><strong>Admin credentials:</strong> admin / admin123</p>';
echo '<p><a href="/admin/login.php">Go to Admin</a> &nbsp;|&nbsp; <a href="/index.php">Go to Site</a></p>';
echo '<p><em>Delete or protect install.php after use.</em></p>';
echo '</body></html>';
