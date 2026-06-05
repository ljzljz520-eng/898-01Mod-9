<?php

namespace Database\Seeders;

use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 创建管理员（如果不存在）
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@forum.com',
                'password' => Hash::make('123456'),
                'role' => 'admin',
                'status' => 1,
            ]
        );

        // 创建普通用户（如果不存在）
        $user1 = User::firstOrCreate(
            ['username' => 'user1'],
            [
                'email' => 'user1@forum.com',
                'password' => Hash::make('123456'),
                'role' => 'user',
                'status' => 1,
            ]
        );

        $user2 = User::firstOrCreate(
            ['username' => 'user2'],
            [
                'email' => 'user2@forum.com',
                'password' => Hash::make('123456'),
                'role' => 'user',
                'status' => 1,
            ]
        );

        // 创建更多用户
        $users = [$user1, $user2];
        for ($i = 3; $i <= 10; $i++) {
            $users[] = User::firstOrCreate(
                ['username' => "user{$i}"],
                [
                    'email' => "user{$i}@forum.com",
                    'password' => Hash::make('123456'),
                    'role' => 'user',
                    'status' => 1,
                ]
            );
        }

        // 帖子标题和内容模板
        $titles = [
            '欢迎来到学习交流论坛',
            '如何高效学习编程？',
            '推荐一些优质的学习资源',
            'Vue.js 3.0 新特性详解',
            'Laravel 最佳实践分享',
            '前端性能优化技巧',
            '数据库设计原则',
            'RESTful API 设计规范',
            'Docker 容器化部署指南',
            'Git 工作流最佳实践',
            'TypeScript 类型系统深入理解',
            'React Hooks 使用心得',
            'Node.js 异步编程模式',
            '微服务架构设计思考',
            '算法与数据结构学习路径',
            '系统设计面试准备',
            '代码重构技巧分享',
            '单元测试编写指南',
            'CI/CD 持续集成实践',
            '网络安全防护措施',
            'Linux 系统管理基础',
            'Redis 缓存使用场景',
            '消息队列选型对比',
            '分布式系统一致性',
            '高并发系统设计',
            '机器学习入门教程',
            '深度学习框架对比',
            '数据可视化工具推荐',
            '项目管理工具使用',
            '团队协作最佳实践',
            '技术文档编写规范',
            '代码审查要点总结',
            '技术债务管理方法',
            '性能监控工具介绍',
            '日志分析实践',
            '错误追踪系统搭建',
            'API 网关设计',
            '服务网格架构',
            '云原生应用开发',
            'Serverless 架构实践',
            'GraphQL vs REST',
            'WebSocket 实时通信',
            'PWA 渐进式 Web 应用',
            '移动端适配方案',
            '响应式设计技巧',
            '无障碍访问优化',
            'SEO 优化策略',
            '网站安全加固',
            'CDN 加速配置',
            '图片优化技巧',
        ];

        $categories = ['general', 'tech', 'study', 'question', 'share'];
        $contents = [
            '这是一个学习交流的论坛，欢迎大家在这里分享知识、讨论问题、共同进步！论坛支持发布主题、回复讨论等功能，希望大家能够积极参与，营造良好的学习氛围。',
            '想和大家讨论一下学习编程的方法和技巧。个人觉得实践很重要，多写代码、多思考、多总结。',
            '分享一些我觉得不错的学习网站和课程，希望对大家有帮助。',
            '最近在学习 Vue.js 3.0，发现了很多新特性和改进，想和大家分享一下。Composition API 的使用体验非常好，性能也有明显提升。',
            '在使用 Laravel 开发项目时，总结了一些最佳实践，包括路由设计、模型关系、中间件使用等。',
            '前端性能优化是一个持续的过程，可以从代码分割、懒加载、缓存策略等多个方面入手。',
            '良好的数据库设计是系统稳定运行的基础，需要遵循范式设计原则，同时考虑实际业务需求。',
            'RESTful API 设计需要遵循统一的规范，包括资源命名、HTTP 方法使用、状态码选择等。',
            'Docker 容器化可以大大简化部署流程，提高开发效率，这里分享一些实践经验。',
            'Git 工作流的选择对团队协作很重要，可以根据团队规模选择合适的流程。',
        ];

        // 创建帖子：按标题去重，避免重复主题（多次执行 Seeder 也不会重复）
        $topics = [];
        foreach ($titles as $index => $title) {
            $user = $users[array_rand($users)];
            $category = $categories[array_rand($categories)];
            // 基于模板内容 + 标题生成，确保每个主题内容也不重复
            $baseContent = $contents[$index % count($contents)];
            $content = $baseContent.'（本主题：'.$title.'）';
            
            // 为前3条帖子设置置顶
            $isPinned = $index < 3 ? 1 : 0;
            
            // 随机生成浏览量和回复数
            $viewCount = rand(10, 500);
            $replyCount = rand(0, 20);
            
            // 以 title 作为唯一键，防止重复主题；若已存在则不再新建
            $topic = Topic::firstOrCreate(
                ['title' => $title],
                [
                    'user_id' => $user->id,
                    'content' => $content,
                    'category' => $category,
                    'view_count' => $viewCount,
                    'reply_count' => $replyCount,
                    'is_pinned' => $isPinned,
                    'status' => 1,
                    'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                ]
            );
            
            $topics[] = $topic;
            
            // 仅在新建主题时创建回复，避免多次 Seeder 时重复堆叠回复
            if ($topic->wasRecentlyCreated) {
                $replyCount = min($replyCount, 10); // 最多10条回复
                $replyContents = [
                    '很好的分享，学到了很多！',
                    '感谢楼主的详细讲解，受益匪浅。',
                    '我也遇到过类似的问题，我的解决方法是...',
                    '这个思路很不错，值得借鉴。',
                    '补充一点，还可以考虑...',
                    '同意楼主的观点，实践确实很重要。',
                    '期待更多类似的分享！',
                    '请问有没有相关的代码示例？',
                    '这个方案在实际项目中效果如何？',
                    '感谢分享，收藏了！',
                ];

                // 打乱顺序后按需取用，保证同一主题下回复内容不重复
                shuffle($replyContents);

                for ($j = 0; $j < $replyCount; $j++) {
                    $replyUser = $users[array_rand($users)];
                    Reply::create([
                        'topic_id' => $topic->id,
                        'user_id' => $replyUser->id,
                        'content' => $replyContents[$j],
                        'status' => 1,
                        'created_at' => $topic->created_at->addHours(rand(1, 48)),
                    ]);
                }
            }
        }
    }
}
