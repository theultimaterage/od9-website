-- Seed initial resources from production + Orion

-- Global Challenges
INSERT INTO od9_resources (title, description, url, category, tags, resource_type, tier_requirement, difficulty_level, is_critical, display_order, icon_class) VALUES
('Doomsday Clock', 'The Bulletin of the Atomic Scientists\' symbolic clock representing how close humanity is to catastrophic destruction. Currently at 90 seconds to midnight - the closest ever.', 'https://thebulletin.org/doomsday-clock/', 'Global Challenges', '["Data", "Critical"]', 'website', NULL, 'all', TRUE, 1, 'fas fa-clock'),
('NASA Climate Change', 'NASA\'s official resource on climate change - scientific evidence, causes, effects, and solutions from the world\'s leading space agency.', 'https://science.nasa.gov/climate-change/', 'Global Challenges', '["Critical", "Science"]', 'website', NULL, 'all', TRUE, 2, 'fas fa-earth-americas'),
('Global Peace Index', 'The world\'s leading measure of global peacefulness, ranking 163 countries. Published by the Institute for Economics and Peace.', 'https://www.visionofhumanity.org/', 'Global Challenges', '["Data", "Annual Report"]', 'website', NULL, 'all', FALSE, 3, 'fas fa-dove'),
('Oligarchy Study (Gilens & Page 2014)', 'Princeton study "Testing Theories of American Politics" proving that economic elites and organized groups have substantial impact on U.S. policy, while average citizens have little influence.', 'https://archive.org/details/gilens_and_page_2014_-testing_theories_of_american_politics.doc', 'Global Challenges', '["Academic", "Study"]', 'study', NULL, 'intermediate', FALSE, 4, 'fas fa-file-pdf'),
('BBC: US is an Oligarchy', 'BBC\'s coverage of the Gilens & Page study, explaining how the research proves the US political system serves the wealthy, not average citizens.', 'https://www.bbc.com/news/blogs-echochambers-27074746', 'Global Challenges', '["Article", "Analysis"]', 'article', NULL, 'beginner', FALSE, 5, 'fas fa-newspaper');

-- Kardashev Scale
INSERT INTO od9_resources (title, description, url, category, tags, resource_type, tier_requirement, difficulty_level, is_critical, display_order, icon_class) VALUES
('What is the Kardashev Scale?', 'Introduction to Nikolai Kardashev\'s classification system for measuring a civilization\'s technological advancement based on energy consumption.', 'https://www.youtube.com/watch?v=rhFK5_Nx9xY', 'Kardashev Scale', '["Video", "Beginner"]', 'video', NULL, 'beginner', FALSE, 1, 'fab fa-youtube'),
('The Fermi Paradox', 'Exploration of why we haven\'t detected intelligent alien life despite the high probability of its existence. Essential context for understanding humanity\'s place in the universe.', 'https://www.youtube.com/watch?v=sNhhvQGsMEc', 'Kardashev Scale', '["Video", "Intermediate"]', 'video', NULL, 'intermediate', FALSE, 2, 'fab fa-youtube'),
('Great Filter Theory', 'Robin Hanson\'s theory about why we don\'t see other civilizations - and what it means for humanity\'s future survival and advancement.', 'https://en.wikipedia.org/wiki/Great_Filter', 'Kardashev Scale', '["Article", "Intermediate"]', 'article', NULL, 'intermediate', FALSE, 3, 'fas fa-external-link-alt');

-- STEAM Optimization
INSERT INTO od9_resources (title, description, url, category, tags, resource_type, tier_requirement, difficulty_level, is_critical, display_order, icon_class) VALUES
('Systems Thinking', 'Learn to see the big picture - how parts interconnect to form complex wholes. Essential for solving coordination failures.', 'https://www.youtube.com/watch?v=GPW0j2Bo_eY', 'STEAM Optimization', '["Course", "Beginner"]', 'video', NULL, 'beginner', FALSE, 1, 'fab fa-youtube'),
('Khan Academy - All Subjects', 'Free world-class education covering math, science, computing, economics, arts, and more. Build your foundational knowledge.', 'https://www.khanacademy.org', 'STEAM Optimization', '["Course", "All Levels"]', 'course', NULL, 'all', FALSE, 2, 'fas fa-graduation-cap'),
('MIT OpenCourseWare', 'Free access to MIT\'s actual course materials across science, engineering, technology, and more.', 'https://ocw.mit.edu', 'STEAM Optimization', '["Course", "Advanced"]', 'course', NULL, 'advanced', FALSE, 3, 'fas fa-university');

-- Transhumanism
INSERT INTO od9_resources (title, description, url, category, tags, resource_type, tier_requirement, difficulty_level, is_critical, display_order, icon_class) VALUES
('What is Transhumanism?', 'Overview of the philosophical movement advocating for the enhancement of human capabilities through technology.', 'https://www.humanityplus.org/the-transhumanist-manifesto', 'Transhumanism', '["Article", "Beginner"]', 'article', NULL, 'beginner', FALSE, 1, 'fas fa-brain'),
('The Singularity Is Near', 'Ray Kurzweil\'s influential book on technological acceleration and the future merger of human and machine intelligence.', 'https://www.amazon.com/Singularity-Near-Humans-Transcend-Biology/dp/0143037889', 'Transhumanism', '["Book", "Advanced"]', 'book', NULL, 'advanced', FALSE, 2, 'fas fa-book'),
('Nick Bostrom\'s Papers', 'Academic work on existential risk, AI safety, and the ethical implications of human enhancement.', 'https://nickbostrom.com/', 'Transhumanism', '["Academic", "Advanced"]', 'website', NULL, 'advanced', FALSE, 3, 'fas fa-user-graduate');

-- AI Tools (INCLUDING ORION!)
INSERT INTO od9_resources (title, description, url, category, tags, resource_type, tier_requirement, difficulty_level, is_critical, display_order, icon_class) VALUES
('Orion AI', 'Your personal AI agent running on your device with full system access. Unlike chat-only AI, Orion takes real action: execute code, manage files, control apps, browse web, automate workflows, generate images/videos/music, and even make phone calls on your behalf. The ultimate AI assistant for productivity and creativity.', 'https://meetorion.app', 'AI Tools', '["Tool", "Essential", "Featured"]', 'tool', NULL, 'all', TRUE, 1, 'fas fa-robot'),
('ChatGPT', 'OpenAI\'s conversational AI assistant for writing, analysis, and problem-solving.', 'https://chat.openai.com', 'AI Tools', '["Tool", "Popular"]', 'tool', NULL, 'beginner', FALSE, 2, 'fas fa-comment-dots'),
('Claude', 'Anthropic\'s advanced AI assistant with strong reasoning and coding capabilities.', 'https://claude.ai', 'AI Tools', '["Tool", "Advanced"]', 'tool', NULL, 'intermediate', FALSE, 3, 'fas fa-brain'),
('Midjourney', 'AI-powered image generation for creative and artistic projects.', 'https://www.midjourney.com', 'AI Tools', '["Tool", "Creative"]', 'tool', NULL, 'beginner', FALSE, 4, 'fas fa-image');

-- OD9 Content
INSERT INTO od9_resources (title, description, url, category, tags, resource_type, tier_requirement, difficulty_level, is_critical, display_order, icon_class) VALUES
('The Ultimate Rage on Substack', 'Essays and analysis from OD9\'s founder covering technology, society, music, and the path to human advancement.', 'https://theultimaterage.substack.com', 'OD9 Content', '["Blog", "All Levels"]', 'website', NULL, 'all', FALSE, 1, 'fas fa-newspaper'),
('OD9 YouTube', 'Video content exploring OD9 concepts, music, and community updates.', 'https://youtube.com/@theultimaterage', 'OD9 Content', '["Video", "All Levels"]', 'video', NULL, 'all', FALSE, 2, 'fab fa-youtube'),
('OD9 Framework Document', 'The complete breakdown of OD9\'s mission, tier system, and path to Type I civilization.', '/framework.php', 'OD9 Content', '["Document", "All Levels"]', 'website', NULL, 'all', FALSE, 3, 'fas fa-book-open');
