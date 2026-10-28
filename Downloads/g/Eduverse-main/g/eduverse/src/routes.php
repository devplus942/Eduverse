<?php
declare(strict_types=1);

return [
    'GET /' => ['AuthController', 'home'],
    'GET /login' => ['AuthController', 'loginForm'],
    'POST /login' => ['AuthController', 'login'],
    'GET /register' => ['AuthController', 'registerForm'],
    'POST /register' => ['AuthController', 'register'],
    'POST /logout' => ['AuthController', 'logout'],

    'GET /courses' => ['StudentController', 'courses'],
    'POST /courses/enroll' => ['StudentController', 'enroll'],
    'GET /course' => ['StudentController', 'course'],
    'GET /lesson' => ['StudentController', 'lesson'],
    'GET /quiz' => ['StudentController', 'quiz'],
    'POST /quiz/submit' => ['StudentController', 'quizSubmit'],
    'GET /quiz/result' => ['StudentController', 'quizResult'],
    'GET /assignment' => ['StudentController', 'assignment'],
    'POST /assignment/submit' => ['StudentController', 'assignmentSubmit'],
    'GET /download' => ['StudentController', 'download'],

    'POST /lecturer/course/create' => ['LecturerController', 'courseCreate'],
    'POST /lecturer/lesson/create' => ['LecturerController', 'lessonCreate'],
    'POST /lecturer/exercise/create' => ['LecturerController', 'exerciseCreate'],
    'POST /lecturer/assignment/create' => ['LecturerController', 'assignmentCreate'],
    'POST /lecturer/session/create' => ['LecturerController', 'sessionCreate'],
    'GET /lecturer/submissions' => ['LecturerController', 'submissions'],
    'POST /lecturer/grade' => ['LecturerController', 'grade'],
    'POST /lecturer/ai-feedback' => ['LecturerController', 'aiFeedback'],
    'POST /lecturer/plagiarism' => ['LecturerController', 'plagiarism'],
    'GET /lecturer/analytics' => ['LecturerController', 'analytics'],
    'GET /lecturer/report' => ['LecturerController', 'report'],
    'GET /lecturer/export' => ['LecturerController', 'export'],

    'GET /admin/users' => ['AdminController', 'users'],
    'POST /admin/users/create' => ['AdminController', 'createUser'],
    'POST /admin/users/toggle' => ['AdminController', 'toggle'],
    'GET /admin/analytics' => ['AdminController', 'analytics'],

    'GET /forum' => ['CollabController', 'forum'],
    'GET /thread' => ['CollabController', 'thread'],
    'POST /thread/create' => ['CollabController', 'threadCreate'],
    'POST /thread/suggest' => ['AiController', 'suggestReply'],
    'POST /post/create' => ['CollabController', 'postCreate'],
    'GET /chat' => ['CollabController', 'chat'],
    'GET /chat/poll' => ['CollabController', 'chatPoll'],
    'POST /chat/send' => ['CollabController', 'chatSend'],

    'GET /chatbot' => ['AiController', 'chatbot'],
    'POST /chatbot/ask' => ['AiController', 'ask'],
];
