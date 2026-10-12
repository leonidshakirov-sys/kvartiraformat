SET NAMES utf8mb4;
SET time_zone = '+03:00';

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(120) PRIMARY KEY,
  setting_value TEXT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(40) NOT NULL DEFAULT 'owner',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX idx_login_attempts (username, ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  meta_title VARCHAR(255) NULL,
  meta_description VARCHAR(500) NULL,
  h1 VARCHAR(255) NULL,
  body MEDIUMTEXT NULL,
  canonical_url VARCHAR(255) NULL,
  robots_index TINYINT(1) NOT NULL DEFAULT 1,
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  short TEXT NULL,
  body MEDIUMTEXT NULL,
  price INT NULL,
  image_path VARCHAR(255) NULL,
  image_alt VARCHAR(255) NULL,
  meta_title VARCHAR(255) NULL,
  meta_description VARCHAR(500) NULL,
  h1 VARCHAR(255) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 100,
  category VARCHAR(40) NOT NULL DEFAULT 'apartment',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_services_category (category, is_published, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS geo_pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  title VARCHAR(255) NULL,
  description VARCHAR(500) NULL,
  h1 VARCHAR(255) NULL,
  body MEDIUMTEXT NULL,
  priority INT NOT NULL DEFAULT 1,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repair_tariffs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  repair_type VARCHAR(120) NOT NULL,
  package_type VARCHAR(160) NOT NULL,
  price_per_m2 INT NOT NULL,
  note TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 100,
  category VARCHAR(40) NOT NULL DEFAULT 'apartment',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_tariff_lookup (repair_type, package_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS advantages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  text TEXT NOT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 100,
  category VARCHAR(40) NOT NULL DEFAULT 'apartment',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_advantages_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faqs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(255) NOT NULL,
  answer TEXT NOT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 100,
  category VARCHAR(40) NOT NULL DEFAULT 'apartment',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_faqs_question (question)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portfolio_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  city VARCHAR(120) NULL,
  district VARCHAR(180) NULL,
  area VARCHAR(80) NULL,
  repair_type VARCHAR(120) NULL,
  cost VARCHAR(120) NULL,
  duration VARCHAR(120) NULL,
  works TEXT NULL,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 100,
  category VARCHAR(40) NOT NULL DEFAULT 'apartment',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_portfolio_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portfolio_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  portfolio_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  kind ENUM('before','after','gallery') NOT NULL DEFAULT 'gallery',
  alt_text VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 100,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_portfolio_images_item FOREIGN KEY (portfolio_id) REFERENCES portfolio_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author_name VARCHAR(160) NOT NULL,
  text TEXT NOT NULL,
  source_url VARCHAR(500) NULL,
  photo_path VARCHAR(255) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  file_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  alt_text VARCHAR(255) NULL,
  mime_type VARCHAR(120) NULL,
  width INT NULL,
  height INT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  public_id VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(160) NOT NULL,
  phone VARCHAR(60) NOT NULL,
  city VARCHAR(160) NULL,
  repair_type VARCHAR(120) NULL,
  property_type VARCHAR(120) NULL,
  area DECIMAL(8,2) NULL,
  rooms VARCHAR(40) NULL,
  package_type VARCHAR(160) NULL,
  extras TEXT NULL,
  comment TEXT NULL,
  estimate_text VARCHAR(255) NULL,
  page_url VARCHAR(500) NULL,
  source VARCHAR(120) NULL,
  utm_source VARCHAR(120) NULL,
  utm_medium VARCHAR(120) NULL,
  utm_campaign VARCHAR(180) NULL,
  utm_content VARCHAR(180) NULL,
  utm_term VARCHAR(180) NULL,
  referrer VARCHAR(500) NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(500) NULL,
  consent_version VARCHAR(80) NOT NULL,
  consent_text TEXT NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'new',
  manager_comment TEXT NULL,
  idempotency_key VARCHAR(80) NOT NULL,
  lead_type VARCHAR(40) NOT NULL DEFAULT 'apartment',
  work_start VARCHAR(160) NULL,
  has_project VARCHAR(80) NULL,
  premise_condition VARCHAR(160) NULL,
  quiz_payload MEDIUMTEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_leads_created (created_at),
  INDEX idx_leads_phone_created (phone, created_at),
  INDEX idx_leads_type (lead_type, created_at),
  UNIQUE KEY uniq_leads_idempotency (idempotency_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(160) NULL,
  size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX idx_lead_files_lead (lead_id),
  CONSTRAINT fk_lead_files_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS telegram_queue (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED NULL,
  message TEXT NOT NULL,
  status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  attempts INT NOT NULL DEFAULT 0,
  last_error TEXT NULL,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_telegram_status (status, attempts, created_at),
  CONSTRAINT fk_telegram_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_user_id INT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  entity VARCHAR(120) NULL,
  entity_id INT UNSIGNED NULL,
  payload JSON NULL,
  ip_address VARCHAR(64) NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value, updated_at) VALUES
('brand_name','КвартираФормат',NOW()),('brand_name_full','КВАРТИРАФОРМАТ',NOW()),('brand_tagline','Ремонт квартир под ключ',NOW()),('service_area','Москва и Московская область',NOW()),('legal_name','ИП Шакиров Леонид Альбертович',NOW()),('inn','772607203680',NOW()),('phone','+7 (916) 265-92-62',NOW()),('phone_href','tel:+79162659262',NOW()),('office_address','г. Москва, 32-й км МКАД, владение 15',NOW()),('domain','kvartiraformat.ru',NOW()),('privacy_version','2026-10-10',NOW()),('ym_counter_id','113599005',NOW()),('route_url','https://yandex.ru/maps/?text=г.%20Москва,%2032-й%20км%20МКАД,%20владение%2015',NOW())
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW();

INSERT INTO services (title, slug, short, body, price, image_path, image_alt, meta_title, meta_description, h1, is_published, sort_order, created_at, updated_at) VALUES
('Ремонт квартир под ключ','remont-kvartir-pod-klyuch','Комплексный ремонт квартиры от подготовки основания до финальной отделки.','<p>Комплексный ремонт под ключ помогает объединить черновые, инженерные и финишные работы в один управляемый процесс. На странице можно добавить состав работ, условия замера и локальные особенности.</p>',NULL,'/assets/images/services/turnkey.webp','Ремонт квартиры под ключ в Москве','Ремонт квартир под ключ в Москве — расчёт стоимости | КвартираФормат','Комплексный ремонт квартир под ключ в Москве и Московской области. Расчёт стоимости, заявка на замер, управление сметой.','Ремонт квартир под ключ в Москве',1,10,NOW(),NOW()),
('Капитальный ремонт','kapitalnyy-remont','Глубокое обновление квартиры с инженерными работами и заменой отделки.','<p>Капитальный ремонт подходит для квартир, где нужно обновить инженерные системы, основания и финишную отделку. Итоговый состав работ определяется после осмотра.</p>',NULL,'/assets/images/services/capital.webp','Капитальный ремонт квартиры','Капитальный ремонт квартир в Москве | КвартираФормат','Капитальный ремонт квартир: демонтаж, черновые работы, инженерия и отделка.','Капитальный ремонт квартир',1,20,NOW(),NOW()),
('Косметический ремонт','kosmeticheskiy-remont','Обновление финишных покрытий без полной перепланировки.','<p>Косметический ремонт позволяет обновить внешний вид квартиры без полного переустройства. Точный перечень зависит от состояния помещений.</p>',NULL,'/assets/images/services/cosmetic.webp','Косметический ремонт светлой комнаты','Косметический ремонт квартиры в Москве | КвартираФормат','Косметический ремонт квартир с расчётом стоимости по параметрам объекта.','Косметический ремонт квартиры',1,30,NOW(),NOW()),
('Дизайнерский ремонт','dizaynerskiy-remont','Ремонт с проработанными решениями по стилю, свету и материалам.','<p>Дизайнерский ремонт требует согласования решений по планировке, свету, материалам и деталям. Страница наполняется примерами и составом работ.</p>',NULL,'/assets/images/services/designer.webp','Дизайнерский ремонт квартиры','Дизайнерский ремонт квартиры в Москве | КвартираФормат','Дизайнерский ремонт квартир в Москве и области: заявка на расчёт и замер.','Дизайнерский ремонт квартиры',1,40,NOW(),NOW()),
('Ремонт квартир в новостройках','remont-novostroek','Отделка квартир без отделки или с предчистовой подготовкой.','<p>Новостройки часто требуют комплексной черновой подготовки, разводки инженерных систем и финишной отделки.</p>',NULL,'/assets/images/services/newbuild.webp','Ремонт квартиры в новостройке','Ремонт квартир в новостройке | КвартираФормат','Ремонт квартир в новостройках в Москве и Подмосковье.','Ремонт квартир в новостройках',1,50,NOW(),NOW()),
('Ремонт вторичного жилья','remont-vtorichnogo-zhilya','Обновление квартир во вторичном фонде с демонтажом старой отделки.','<p>Во вторичном жилье важны обследование старой отделки, демонтаж и проверка инженерных систем.</p>',NULL,'/assets/images/services/secondary.webp','Ремонт вторичного жилья','Ремонт вторички под ключ | КвартираФормат','Ремонт квартир во вторичном жилом фонде: расчёт, замер, смета.','Ремонт вторичного жилья',1,60,NOW(),NOW()),
('Ремонт студий','remont-studiy','Компактные решения для квартир-студий и апартаментов.','<p>Ремонт студии требует особенно точной планировки хранения, света и зонирования. Добавьте реальные примеры после появления объектов.</p>',NULL,'/assets/images/services/studio.webp','Ремонт современной квартиры-студии','Ремонт студий в Москве | КвартираФормат','Ремонт квартир-студий и апартаментов под ключ с расчётом стоимости.','Ремонт студий',1,70,NOW(),NOW()),
('Ремонт однокомнатных квартир','remont-odnokomnatnyh-kvartir','Планирование бюджета и этапов ремонта однокомнатной квартиры.','<p>Однокомнатные квартиры часто ремонтируются для проживания или сдачи в аренду. Состав работ уточняется после замера.</p>',NULL,'/assets/images/services/one-room.webp','Ремонт однокомнатной квартиры','Ремонт однокомнатной квартиры | КвартираФормат','Ремонт однокомнатных квартир в Москве и Московской области.','Ремонт однокомнатных квартир',1,80,NOW(),NOW()),
('Ремонт двухкомнатных квартир','remont-dvuhkomnatnyh-kvartir','Комплексный ремонт для семейного проживания или сдачи в аренду.','<p>Для двухкомнатных квартир важно согласовать этапность, хранение материалов и доступ к помещениям.</p>',NULL,'/assets/images/services/two-room.webp','Ремонт двухкомнатной квартиры','Ремонт двухкомнатной квартиры | КвартираФормат','Ремонт двухкомнатных квартир под ключ: расчёт и заявка.','Ремонт двухкомнатных квартир',1,90,NOW(),NOW()),
('Ремонт трёхкомнатных квартир','remont-trehkomnatnyh-kvartir','Поэтапная организация ремонта квартир большой площади.','<p>В трёхкомнатных квартирах требуется детальная смета по помещениям и этапам работ.</p>',NULL,'/assets/images/services/three-room.webp','Ремонт трёхкомнатной квартиры','Ремонт трёхкомнатной квартиры | КвартираФормат','Ремонт трёхкомнатных квартир под ключ в Москве и области.','Ремонт трёхкомнатных квартир',1,100,NOW(),NOW()),
('Ремонт ванной и санузла','remont-vannoy-i-sanuzla','Плиточные, сантехнические и отделочные работы в мокрых зонах.','<p>Ремонт ванной и санузла требует внимательного подхода к гидроизоляции, сантехнике, плитке и вентиляции.</p>',NULL,'/assets/images/services/bathroom.webp','Ремонт ванной и санузла','Ремонт ванной и санузла | КвартираФормат','Ремонт ванной комнаты и санузла: заявка на расчёт работ.','Ремонт ванной и санузла',1,110,NOW(),NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title), short=VALUES(short), image_path=VALUES(image_path), image_alt=VALUES(image_alt), updated_at=NOW();

INSERT INTO geo_pages (name, slug, title, description, h1, body, priority, is_published, created_at, updated_at) VALUES
('Москва','moskva','Ремонт квартир под ключ в Москве — расчёт стоимости | КвартираФормат','Ремонт квартир под ключ в Москве: услуги, расчёт стоимости и заявка на замер.','Ремонт квартир под ключ в Москве','<p>Для Москвы важно учитывать тип дома, правила проведения шумных работ и логистику материалов. Добавьте реальные примеры объектов после их появления.</p>',1,1,NOW(),NOW()),
('Домодедово','domodedovo','Ремонт квартир под ключ в Домодедово — расчёт стоимости | КвартираФормат','Ремонт квартир под ключ в Домодедово: расчёт стоимости, виды ремонта и заявка.','Ремонт квартир под ключ в Домодедово','<p>В Домодедово востребованы ремонты квартир в новостройках и вторичном жилье. Страница готова для добавления локальных кейсов.</p>',1,1,NOW(),NOW()),
('Подольск','podolsk','Ремонт квартир под ключ в Подольске — расчёт стоимости | КвартираФормат','Ремонт квартир в Подольске с расчётом стоимости и заявкой на замер.','Ремонт квартир под ключ в Подольске','<p>Подольск входит в приоритетную географию сайта. Добавляйте полезную локальную информацию и реальные объекты.</p>',1,1,NOW(),NOW()),
('Видное','vidnoe','Ремонт квартир под ключ в Видном — расчёт стоимости | КвартираФормат','Ремонт квартир в Видном: услуги, калькулятор и заявка.','Ремонт квартир под ключ в Видном','<p>Страница предназначена для аккуратного SEO-продвижения без массовой генерации пустых посадочных.</p>',1,1,NOW(),NOW()),
('Щербинка','shcherbinka','Ремонт квартир под ключ в Щербинке — расчёт стоимости | КвартираФормат','Ремонт квартир в Щербинке: комплексный подход и заявка на расчёт.','Ремонт квартир под ключ в Щербинке','<p>Опишите особенности ремонта квартир в Щербинке и добавьте реальные кейсы после выполнения работ.</p>',1,1,NOW(),NOW()),
('Южное Бутово','yuzhnoe-butovo','Ремонт квартир под ключ в Южном Бутово — расчёт стоимости | КвартираФормат','Ремонт квартир в Южном Бутово: калькулятор и заявка на замер.','Ремонт квартир под ключ в Южном Бутово','<p>Южное Бутово включено в первую очередь географии. Текст можно расширить через админ-панель.</p>',1,1,NOW(),NOW()),
('Северное Бутово','severnoe-butovo','Ремонт квартир под ключ в Северном Бутово — расчёт стоимости | КвартираФормат','Ремонт квартир в Северном Бутово: услуги, расчёт и заявка.','Ремонт квартир под ключ в Северном Бутово','<p>Страница для локального спроса в Северном Бутово. Добавьте реальные примеры и особенности домов после сбора данных.</p>',1,1,NOW(),NOW()),
('Чертаново','chertanovo','Ремонт квартир под ключ в Чертаново — расчёт стоимости | КвартираФормат','Ремонт квартир в Чертаново: виды ремонта и заявка на расчёт.','Ремонт квартир под ключ в Чертаново','<p>Чертаново входит в приоритетную географию. Текст можно расширять локальными примерами и полезными деталями.</p>',1,1,NOW(),NOW()),
('Бирюлёво','biryulevo','Ремонт квартир под ключ в Бирюлёво — расчёт стоимости | КвартираФормат','Ремонт квартир в Бирюлёво: калькулятор, услуги и форма заявки.','Ремонт квартир под ключ в Бирюлёво','<p>Страница Бирюлёво подготовлена для уникального наполнения без искусственного повторения топонимов.</p>',1,1,NOW(),NOW()),
('Орехово-Борисово','orehovo-borisovo','Ремонт квартир под ключ в Орехово-Борисово — расчёт стоимости | КвартираФормат','Ремонт квартир в Орехово-Борисово: расчёт стоимости и консультация.','Ремонт квартир под ключ в Орехово-Борисово','<p>Опишите особенности ремонта в Орехово-Борисово и добавьте локальные кейсы при их появлении.</p>',1,1,NOW(),NOW()),
('Чехов','chehov','Ремонт квартир под ключ в Чехове — расчёт стоимости | КвартираФормат','Ремонт квартир в Чехове: услуги и заявка на расчёт.','Ремонт квартир под ключ в Чехове','<p>Страница второй очереди. Публикуйте после подготовки полезного локального содержания.</p>',2,1,NOW(),NOW()),
('Серпухов','serpuhov','Ремонт квартир под ключ в Серпухове — расчёт стоимости | КвартираФормат','Ремонт квартир в Серпухове: расчёт стоимости и заявка.','Ремонт квартир под ключ в Серпухове','<p>Страница второй очереди для аккуратного развития географии.</p>',2,1,NOW(),NOW()),
('Ступино','stupino','Ремонт квартир под ключ в Ступино — расчёт стоимости | КвартираФормат','Ремонт квартир в Ступино: виды ремонта и форма заявки.','Ремонт квартир под ключ в Ступино','<p>Добавьте реальные локальные примеры перед активным продвижением страницы.</p>',2,1,NOW(),NOW()),
('Кашира','kashira','Ремонт квартир под ключ в Кашире — расчёт стоимости | КвартираФормат','Ремонт квартир в Кашире: калькулятор и консультация.','Ремонт квартир под ключ в Кашире','<p>Страница второй очереди с возможностью последующего уникального наполнения.</p>',2,1,NOW(),NOW()),
('Одинцово','odincovo','Ремонт квартир под ключ в Одинцово — расчёт стоимости | КвартираФормат','Ремонт квартир в Одинцово: услуги, расчёт и заявка.','Ремонт квартир под ключ в Одинцово','<p>Страница второй очереди для локального спроса в Одинцово.</p>',2,1,NOW(),NOW()),
('Красногорск','krasnogorsk','Ремонт квартир под ключ в Красногорске — расчёт стоимости | КвартираФормат','Ремонт квартир в Красногорске: заявка на расчёт и замер.','Ремонт квартир под ключ в Красногорске','<p>Страница второй очереди для развития географии сайта.</p>',2,1,NOW(),NOW()),
('Люберцы','lyubercy','Ремонт квартир под ключ в Люберцах — расчёт стоимости | КвартираФормат','Ремонт квартир в Люберцах: услуги и калькулятор.','Ремонт квартир под ключ в Люберцах','<p>Добавьте полезные локальные детали перед продвижением страницы.</p>',2,1,NOW(),NOW()),
('Балашиха','balashiha','Ремонт квартир под ключ в Балашихе — расчёт стоимости | КвартираФормат','Ремонт квартир в Балашихе: расчёт стоимости и заявка.','Ремонт квартир под ключ в Балашихе','<p>Страница второй очереди для будущего наполнения кейсами.</p>',2,1,NOW(),NOW()),
('Котельники','kotelniki','Ремонт квартир под ключ в Котельниках — расчёт стоимости | КвартираФормат','Ремонт квартир в Котельниках: калькулятор и форма заявки.','Ремонт квартир под ключ в Котельниках','<p>Страница второй очереди. Не публикуйте массово без уникальной ценности.</p>',2,1,NOW(),NOW()),
('Реутов','reutov','Ремонт квартир под ключ в Реутове — расчёт стоимости | КвартираФормат','Ремонт квартир в Реутове: услуги и заявка.','Ремонт квартир под ключ в Реутове','<p>Страница второй очереди для аккуратного SEO-развития.</p>',2,1,NOW(),NOW()),
('Химки','himki','Ремонт квартир под ключ в Химках — расчёт стоимости | КвартираФормат','Ремонт квартир в Химках: расчёт стоимости и консультация.','Ремонт квартир под ключ в Химках','<p>Страница второй очереди для наполнения реальными локальными примерами.</p>',2,1,NOW(),NOW()),
('Мытищи','mytishchi','Ремонт квартир под ключ в Мытищах — расчёт стоимости | КвартираФормат','Ремонт квартир в Мытищах: калькулятор и заявка.','Ремонт квартир под ключ в Мытищах','<p>Страница второй очереди с возможностью добавления объектов.</p>',2,1,NOW(),NOW()),
('Королёв','korolev','Ремонт квартир под ключ в Королёве — расчёт стоимости | КвартираФормат','Ремонт квартир в Королёве: услуги и расчёт стоимости.','Ремонт квартир под ключ в Королёве','<p>Добавьте уникальное описание и кейсы перед активным продвижением.</p>',2,1,NOW(),NOW()),
('Троицк','troick','Ремонт квартир под ключ в Троицке — расчёт стоимости | КвартираФормат','Ремонт квартир в Троицке: форма заявки и калькулятор.','Ремонт квартир под ключ в Троицке','<p>Страница второй очереди для будущего локального продвижения.</p>',2,1,NOW(),NOW()),
('Коммунарка','kommunarka','Ремонт квартир под ключ в Коммунарке — расчёт стоимости | КвартираФормат','Ремонт квартир в Коммунарке: услуги и заявка на расчёт.','Ремонт квартир под ключ в Коммунарке','<p>Страница второй очереди, которую можно расширить особенностями новостроек района.</p>',2,1,NOW(),NOW()),
('Московский','moskovskiy','Ремонт квартир под ключ в Московском — расчёт стоимости | КвартираФормат','Ремонт квартир в Московском: калькулятор и заявка.','Ремонт квартир под ключ в Московском','<p>Страница второй очереди для развития локальной семантики.</p>',2,1,NOW(),NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), updated_at=NOW();

INSERT INTO advantages (title, text, is_published, sort_order, created_at, updated_at) VALUES
('Подробная смета','Фиксируем состав работ и материалов в понятном документе до старта ремонта.',1,10,NOW(),NOW()),('Организация ремонта под ключ','Помогаем пройти путь от черновой отделки до готового интерьера.',1,20,NOW(),NOW()),('Поэтапный контроль качества','Работы принимаются этапами, чтобы замечания не копились до финала.',1,30,NOW(),NOW()),('Работа по договору','Существенные условия и объём взаимодействия фиксируются документально.',1,40,NOW(),NOW()),('Помощь с материалами','Подсказываем по подбору, закупке и доставке черновых материалов.',1,50,NOW(),NOW()),('Фотоотчёты','Можно настроить регулярную отправку фотографий выполненных этапов.',1,60,NOW(),NOW()),('Согласование сроков','План работ формируется с учётом особенностей квартиры и материалов.',1,70,NOW(),NOW()),('Гарантийные обязательства','Условия гарантии указываются в договоре и не подменяются рекламными обещаниями.',1,80,NOW(),NOW())
ON DUPLICATE KEY UPDATE text=VALUES(text), sort_order=VALUES(sort_order), updated_at=NOW();

INSERT INTO faqs (question, answer, is_published, sort_order, created_at, updated_at) VALUES
('Можно ли узнать стоимость ремонта онлайн?','Да. Калькулятор собирает параметры квартиры и помогает оставить заявку на расчёт. Точная смета готовится после уточнения деталей и замера.',1,10,NOW(),NOW()),('Почему часть цен указана как «По расчёту»?','Мы не публикуем неподтверждённые цены. Стоимость считается по площади, состоянию квартиры и составу работ после осмотра.',1,20,NOW(),NOW()),('Работаете ли вы с новостройками?','Да, предусмотрены сценарии ремонта квартир без отделки и с предчистовой подготовкой.',1,30,NOW(),NOW()),('Можно ли заказать капитальный ремонт вторичного жилья?','Да. Для вторичного жилья учитываются демонтаж, состояние инженерных систем и ограничения дома.',1,40,NOW(),NOW()),('Можно ли делать ремонт с материалами?','Калькулятор поддерживает варианты только работ, работ с черновыми материалами и комплексного ремонта.',1,50,NOW(),NOW()),('Нужен ли выезд на замер?','Для точной сметы замер и осмотр объекта обычно необходимы.',1,60,NOW(),NOW()),('Как обрабатываются персональные данные?','Заявки сохраняются в базе данных, факт согласия фиксируется. Документы доступны на отдельных страницах.',1,70,NOW(),NOW()),('Можно ли изменить телефон и реквизиты на сайте?','Актуальные телефон, ИНН и адрес офиса указаны в разделе «Контакты» и в подвале сайта.',1,80,NOW(),NOW()),('Как отправляются заявки в Telegram?','Заявка сохраняется на сайте. После обработки с вами свяжется менеджер по указанному телефону.',1,90,NOW(),NOW()),('Можно ли создать страницу под конкретный город?','Да. Мы работаем в Москве и Московской области, включая города и районы, указанные в разделе географии.',1,100,NOW(),NOW())
ON DUPLICATE KEY UPDATE answer=VALUES(answer), sort_order=VALUES(sort_order), updated_at=NOW();

INSERT INTO portfolio_items (title, city, district, area, repair_type, cost, duration, works, is_demo, is_published, sort_order, created_at, updated_at) VALUES
('Пример гостиной после ремонта','','','','Интерьер гостиной','','','',0,1,10,NOW(),NOW()),
('Пример кухни после ремонта','','','','Кухня','','','',0,1,20,NOW(),NOW()),
('Пример ванной после ремонта','','','','Ванная и санузел','','','',0,1,30,NOW(),NOW()),
('Пример спальни после ремонта','','','','Спальня','','','',0,1,40,NOW(),NOW()),
('Пример прихожей после ремонта','','','','Прихожая','','','',0,1,50,NOW(),NOW()),
('Пример семейной квартиры после ремонта','','','','Семейный интерьер','','','',0,1,60,NOW(),NOW())
ON DUPLICATE KEY UPDATE works=VALUES(works), is_demo=VALUES(is_demo), repair_type=VALUES(repair_type), updated_at=NOW();

INSERT INTO settings (setting_key, setting_value, updated_at) VALUES
('commercial_meta_title','Ремонт коммерческих помещений под ключ в Москве | КвартираФормат',NOW()),
('commercial_meta_description','Ремонт коммерческих помещений в Москве и МО: офисы, магазины, салоны красоты, кафе и склады. Капитальный и косметический ремонт. Рассчитаем стоимость по смете.',NOW()),
('commercial_h1','Ремонт коммерческих помещений под ключ в Москве и Московской области',NOW()),
('commercial_intro','Ремонтируем офисы, магазины, салоны красоты и другие нежилые помещения в Москве и Московской области. Выполняем отделочные и комплексные ремонтные работы. Рассчитаем стоимость с учётом площади, состояния объекта и технического задания.',NOW()),
('commercial_hub_published','1',NOW()),
('schema_commercial_v1','1',NOW())
ON DUPLICATE KEY UPDATE setting_key=setting_key;

INSERT INTO services (title, slug, short, body, price, image_path, image_alt, meta_title, meta_description, h1, is_published, sort_order, category, created_at, updated_at) VALUES
('Ремонт офисов под ключ','remont-ofisov','Капитальный и косметический ремонт офисных помещений. Перегородки, полы, потолки, стены, электромонтаж и внутренняя отделка.','<p>Для собственника или арендатора офиса важны не рекламные обещания, а понятный объём работ. Смета появляется после осмотра и согласования технического задания.</p>',NULL,'/assets/images/commercial/office.webp','Ремонт офиса под ключ: коридор open space со стеклянными перегородками после отделки','Ремонт офисов под ключ в Москве | КвартираФормат','Капитальный и косметический ремонт офисных помещений в Москве и МО. Перегородки, отделка, электромонтаж. Рассчитаем смету по площади и ТЗ.','Ремонт офисов под ключ в Москве и Московской области',1,10,'commercial',NOW(),NOW()),
('Ремонт магазинов и торговых помещений','remont-magazinov','Комплексная отделка магазинов, торговых площадей, шоурумов и помещений в торговых центрах.','<p>Ремонт магазина привязан к дате открытия и правилам объекта. Стоимость зависит от состояния помещения и состава чистовой отделки.</p>',NULL,'/assets/images/commercial/store.webp','Ремонт магазина под ключ: торговый зал с освещением, стеллажами и отделкой','Ремонт магазинов и торговых помещений в Москве | КвартираФормат','Комплексная отделка магазинов, шоурумов и торговых площадей в Москве и Московской области. Подготовим смету по техническому заданию.','Ремонт магазинов и торговых помещений под ключ',1,20,'commercial',NOW(),NOW()),
('Ремонт салонов красоты','remont-salonov-krasoty','Ремонт парикмахерских, барбершопов, студий красоты и косметологических кабинетов с учётом назначения и инженерных требований.','<p>Салон требует мокрые зоны, розетки под оборудование и устойчивые покрытия. Смету считаем после осмотра.</p>',NULL,'/assets/images/commercial/salon.webp','Ремонт салона красоты под ключ: зона обслуживания с зеркалами и чистовой отделкой','Ремонт салонов красоты под ключ в Москве | КвартираФормат','Ремонт парикмахерских, барбершопов и студий красоты с учётом инженерных требований. Расчёт стоимости по площади и составу работ.','Ремонт салонов красоты под ключ в Москве',1,30,'commercial',NOW(),NOW()),
('Ремонт кафе и ресторанов','remont-kafe-restoranov','Внутренняя отделка помещений общественного питания, работы с поверхностями и инженерными системами согласно согласованному проекту.','<p>В общепите отделка зала и служебных зон идёт по проекту. Смету готовим после осмотра, без шаблонной ставки.</p>',NULL,'/assets/images/commercial/cafe.webp','Ремонт кафе под ключ: зал общественного питания после внутренней отделки','Ремонт кафе и ресторанов в Москве | КвартираФормат','Внутренняя отделка помещений общественного питания в Москве и МО. Работы по согласованному проекту. Индивидуальный расчёт сметы.','Ремонт кафе и ресторанов под ключ',1,40,'commercial',NOW(),NOW()),
('Ремонт складских и производственных помещений','remont-skladskih-pomescheniy','Отделочные работы, восстановление покрытий, ремонт подсобных, административных и производственных зон с учётом технического задания.','<p>На складе важны покрытие пола, свет и точное попадание в техническое задание. Универсальную цену за м² не публикуем.</p>',NULL,'/assets/images/commercial/warehouse.webp','Ремонт складского помещения: аккуратные стеллажные зоны и подготовленные покрытия','Ремонт складских и производственных помещений | КвартираФормат','Отделка и восстановление покрытий складов и производственных зон в Москве и Московской области. Смета по техническому заданию.','Ремонт складских и производственных помещений',1,50,'commercial',NOW(),NOW()),
('Ремонт помещений свободного назначения','remont-pomescheniy-svobodnogo-naznacheniya','Подготовка коммерческих помещений к открытию бизнеса, перепланировке и дальнейшему использованию в рамках согласованного проекта.','<p>ПСН часто приходит как коробка. Состав работ зависит от будущего сценария использования и наличия проекта.</p>',NULL,'/assets/images/commercial/free-purpose.webp','Ремонт помещения свободного назначения: светлый коммерческий объём со стеклянными перегородками','Ремонт помещений свободного назначения в Москве | КвартираФормат','Подготовка коммерческих помещений свободного назначения к открытию бизнеса. Отделка, перепланировка в рамках согласованного проекта.','Ремонт помещений свободного назначения',1,60,'commercial',NOW(),NOW())
ON DUPLICATE KEY UPDATE slug=slug;

UPDATE services SET price = 18000 WHERE slug = 'remont-kvartir-pod-klyuch' AND (price IS NULL OR price = 0);
UPDATE services SET price = 16000 WHERE slug = 'kapitalnyy-remont' AND (price IS NULL OR price = 0);
UPDATE services SET price = 8500 WHERE slug = 'kosmeticheskiy-remont' AND (price IS NULL OR price = 0);
UPDATE services SET price = 28000 WHERE slug = 'dizaynerskiy-remont' AND (price IS NULL OR price = 0);
UPDATE services SET price = 15000 WHERE slug = 'remont-novostroek' AND (price IS NULL OR price = 0);
UPDATE services SET price = 17000 WHERE slug = 'remont-vtorichnogo-zhilya' AND (price IS NULL OR price = 0);
UPDATE services SET price = 16000 WHERE slug = 'remont-studiy' AND (price IS NULL OR price = 0);
UPDATE services SET price = 15000 WHERE slug = 'remont-odnokomnatnyh-kvartir' AND (price IS NULL OR price = 0);
UPDATE services SET price = 15500 WHERE slug = 'remont-dvuhkomnatnyh-kvartir' AND (price IS NULL OR price = 0);
UPDATE services SET price = 16000 WHERE slug = 'remont-trehkomnatnyh-kvartir' AND (price IS NULL OR price = 0);
UPDATE services SET price = 22000 WHERE slug = 'remont-vannoy-i-sanuzla' AND (price IS NULL OR price = 0);
UPDATE services SET price = 14000 WHERE slug = 'remont-ofisov' AND (price IS NULL OR price = 0);
UPDATE services SET price = 16000 WHERE slug = 'remont-magazinov' AND (price IS NULL OR price = 0);
UPDATE services SET price = 18000 WHERE slug = 'remont-salonov-krasoty' AND (price IS NULL OR price = 0);
UPDATE services SET price = 20000 WHERE slug = 'remont-kafe-restoranov' AND (price IS NULL OR price = 0);
UPDATE services SET price = 9000 WHERE slug = 'remont-skladskih-pomescheniy' AND (price IS NULL OR price = 0);
UPDATE services SET price = 13000 WHERE slug = 'remont-pomescheniy-svobodnogo-naznacheniya' AND (price IS NULL OR price = 0);

INSERT INTO repair_tariffs (repair_type, package_type, price_per_m2, note, is_active, sort_order, category, created_at, updated_at) VALUES
('Косметический','Только работы',8500,'Стартовая цена за м²',1,10,'apartment',NOW(),NOW()),
('Косметический','Работы с черновыми материалами',11000,'Стартовая цена за м²',1,20,'apartment',NOW(),NOW()),
('Косметический','Комплексный ремонт',13000,'Стартовая цена за м²',1,30,'apartment',NOW(),NOW()),
('Капитальный','Только работы',16000,'Стартовая цена за м²',1,40,'apartment',NOW(),NOW()),
('Капитальный','Работы с черновыми материалами',21000,'Стартовая цена за м²',1,50,'apartment',NOW(),NOW()),
('Капитальный','Комплексный ремонт',25000,'Стартовая цена за м²',1,60,'apartment',NOW(),NOW()),
('Дизайнерский','Только работы',28000,'Стартовая цена за м²',1,70,'apartment',NOW(),NOW()),
('Дизайнерский','Работы с черновыми материалами',34000,'Стартовая цена за м²',1,80,'apartment',NOW(),NOW()),
('Дизайнерский','Комплексный ремонт',39000,'Стартовая цена за м²',1,90,'apartment',NOW(),NOW()),
('Офис','Только работы',14000,'Стартовая цена за м²',1,10,'commercial',NOW(),NOW()),
('Магазин','Только работы',16000,'Стартовая цена за м²',1,20,'commercial',NOW(),NOW()),
('Салон красоты','Только работы',18000,'Стартовая цена за м²',1,30,'commercial',NOW(),NOW()),
('Кафе или ресторан','Только работы',20000,'Стартовая цена за м²',1,40,'commercial',NOW(),NOW()),
('Склад','Только работы',9000,'Стартовая цена за м²',1,50,'commercial',NOW(),NOW()),
('Помещение свободного назначения','Только работы',13000,'Стартовая цена за м²',1,60,'commercial',NOW(),NOW());


