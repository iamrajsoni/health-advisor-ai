# Health Advisor AI - Walkthrough

## ✅ What Was Built

A **self-learning Health Advisor AI** web application using PHP, JavaScript, and the Gemini Flash 2.5 API.

### Key Features
- 🔑 **Settings page** to add/save Gemini API key to file
- 👤 **Member system** with individual folders for each user
- 💬 **Modern chat interface** with Gemini-style design
- 🧠 **Self-learning engine** that learns from past conversations
- 📁 **File-based storage** - no database required

---

## 📁 Project Structure

```
health_advaicer_ai/
├── index.php          # Main chat interface
├── login.php          # Member login/register
├── settings.php       # API key settings
├── api/
│   ├── chat.php       # Chat with self-learning
│   ├── auth.php       # Authentication
│   ├── settings.php   # API key management
│   └── history.php    # Chat history
├── includes/
│   ├── gemini.php     # Gemini Flash 2.5 API
│   ├── self_learn.php # Self-learning engine
│   ├── storage.php    # File operations
│   └── auth_check.php # Session management
├── assets/css/        # Stylesheets
├── assets/js/         # JavaScript
├── config/            # API key storage
├── members/           # User folders & chats
└── knowledge_base/    # Learned Q&A pairs
```

---

## 🧠 Self-Learning Flow

1. User asks question
2. Check knowledge base for similar question (≥60% match)
3. If no match, check user's chat history
4. If still no match, call Gemini API
5. Save response to knowledge base and user's chats
6. Return response to user

The system **learns and remembers** - responses get faster over time!

![Self Learning Flowchart](assets/images/flowchart.png)

---

## 🚀 Setup Instructions

### 1. Deploy the application
Copy files to your PHP web server.

### 2. Get Gemini API Key
1. Go to [Google AI Studio](https://aistudio.google.com/app/apikey)
2. Create an API key
3. Copy the key

### 3. Configure the app
1. Open the app in browser
2. Register a new account
3. Go to **Settings**
4. Paste your API key and save

### 4. Start chatting!
Ask health questions and watch the AI learn.

---

## 🔍 Verification

- ✅ All PHP files have no syntax errors
- ✅ Project structure created correctly
- ✅ 24 files across all directories
- ✅ Modern UI with glassmorphism design
- ✅ Self-learning engine with keyword matching
- ✅ Chat history saved per member
- ✅ API key stored securely in file

---

## 📝 How It Works

1. **First Question**: AI calls Gemini API, saves answer to knowledge base
2. **Similar Question Later**: AI finds cached answer (shows "📚 Learned" badge)
3. **From Your History**: AI uses your past conversations (shows "💬 From Your History")
4. **Fresh Response**: AI calls Gemini API (shows "✨ Fresh Response")

The more you use it, the smarter and faster it becomes! 🎉
