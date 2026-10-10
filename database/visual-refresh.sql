-- Обновление визуала и публичных формулировок на уже развёрнутом сайте.
-- Импорт в phpMyAdmin не обязателен: фото подставляются из файлов автоматически.

UPDATE services SET image_path='/assets/images/services/turnkey.webp', image_alt='Ремонт квартиры под ключ в Москве', updated_at=NOW() WHERE slug='remont-kvartir-pod-klyuch';
UPDATE services SET image_path='/assets/images/services/capital.webp', image_alt='Капитальный ремонт квартиры', updated_at=NOW() WHERE slug='kapitalnyy-remont';
UPDATE services SET image_path='/assets/images/services/cosmetic.webp', image_alt='Косметический ремонт светлой комнаты', updated_at=NOW() WHERE slug='kosmeticheskiy-remont';
UPDATE services SET image_path='/assets/images/services/designer.webp', image_alt='Дизайнерский ремонт квартиры', updated_at=NOW() WHERE slug='dizaynerskiy-remont';
UPDATE services SET image_path='/assets/images/services/newbuild.webp', image_alt='Ремонт квартиры в новостройке', updated_at=NOW() WHERE slug='remont-novostroek';
UPDATE services SET image_path='/assets/images/services/secondary.webp', image_alt='Ремонт вторичного жилья', updated_at=NOW() WHERE slug='remont-vtorichnogo-zhilya';
UPDATE services SET image_path='/assets/images/services/studio.webp', image_alt='Ремонт современной квартиры-студии', updated_at=NOW() WHERE slug='remont-studiy';
UPDATE services SET image_path='/assets/images/services/one-room.webp', image_alt='Ремонт однокомнатной квартиры', updated_at=NOW() WHERE slug='remont-odnokomnatnyh-kvartir';
UPDATE services SET image_path='/assets/images/services/two-room.webp', image_alt='Ремонт двухкомнатной квартиры', updated_at=NOW() WHERE slug='remont-dvuhkomnatnyh-kvartir';
UPDATE services SET image_path='/assets/images/services/three-room.webp', image_alt='Ремонт трёхкомнатной квартиры', updated_at=NOW() WHERE slug='remont-trehkomnatnyh-kvartir';
UPDATE services SET image_path='/assets/images/services/bathroom.webp', image_alt='Ремонт ванной и санузла', updated_at=NOW() WHERE slug='remont-vannoy-i-sanuzla';

UPDATE faqs SET answer='Да. Калькулятор собирает параметры квартиры и помогает оставить заявку на расчёт. Точная смета готовится после уточнения деталей и замера.', updated_at=NOW() WHERE question LIKE 'Можно ли узнать стоимость ремонта онлайн%';
UPDATE faqs SET answer='Мы не публикуем неподтверждённые цены. Стоимость считается по площади, состоянию квартиры и составу работ после осмотра.', updated_at=NOW() WHERE question LIKE 'Почему%«По расчёту»%';
UPDATE faqs SET answer='Актуальные телефон, ИНН и адрес офиса указаны в разделе «Контакты» и в подвале сайта.', updated_at=NOW() WHERE question LIKE 'Можно ли изменить телефон%';
UPDATE faqs SET answer='Заявка сохраняется на сайте. После обработки с вами свяжется менеджер по указанному телефону.', updated_at=NOW() WHERE question LIKE 'Как отправляются заявки в Telegram%';
UPDATE faqs SET answer='Да. Мы работаем в Москве и Московской области, включая города и районы, указанные в разделе географии.', updated_at=NOW() WHERE question LIKE 'Можно ли создать страницу под конкретный город%';
