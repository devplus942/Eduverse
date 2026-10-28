# AI design and limitations

**What is AI here:** the study-tutor chatbot and the advisory essay feedback call a hosted language model through an OpenAI-compatible chat-completions endpoint (default: the Hugging Face router). The model is whatever you set in `HF_MODEL`; no model is bundled, trained or fine-tuned by this project.

**Forum reply suggestions:** a draft is generated from the last five posts and placed in the reply box for the user to edit; it is never posted automatically.

**What is not AI:** recommendations (fixed rule: latest quiz below 60% first, then unattempted quizzes), analytics flags (average below 50%) and plagiarism (Jaccard similarity of word 3-grams). They are deterministic rules and are labelled as such in the UI.

**Service layer:** `AiService` selects a provider (`HuggingFaceProvider` or `MockProvider`), enforces `AI_RATE_LIMIT_PER_HOUR` per user, logs every call, and converts provider failures to friendly messages. Providers return `AiResult` instead of throwing.

**Mock mode:** with no token/model (or `AI_MODE=mock`) replies are a fixed placeholder starting with `[MOCK RESPONSE - not AI generated]`, flagged `is_mock` in the database and badged in the UI.

**Limitations:** the model can be wrong or biased; essay scores are suggestions and a lecturer must grade; only typed text and text extracted from .txt/.docx files is analysed; there is no retrieval over course material; prompts are sent to a third party, so students should not enter sensitive personal data; costs, rate limits and model availability depend on your Hugging Face account and must be checked against current Hugging Face documentation. No accuracy claims are made: the thesis "over 80%" chatbot figure has no stated method and is not reproduced.
