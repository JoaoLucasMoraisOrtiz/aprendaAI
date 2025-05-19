# AprendaAI - Database Schema and Migrations Refactoring

## Changes Made

### Database Schema (PlantUML)
- Updated the diagram to reflect all entities and attributes in the application
- Adjusted relationship between Question and Exam to use many-to-many
- Added missing fields to Question entity: llm_explanation, llm_metadata, is_llm_generated, difficulty_score, topic_tags
- Updated LLMInteraction interaction_type enum to include performance_analysis options
- Standardized difficulty level values across all entities to 'easy', 'medium', 'hard'

### Migrations
- Updated the questions table migration to remove direct exam_id foreign key
- Added new fields to questions table: llm_explanation, llm_metadata, is_llm_generated, difficulty_score, topic_tags
- Ensured explanation_cache table uses string for difficulty_level instead of enum

### Models
- Created new ExamQuestion pivot model to manage the many-to-many relationship
- Updated Question and Exam models to use belongsToMany relationship
- Added relationship for ExplanationCaches to the Question model
- Removed incorrect explanationCaches relationship from User model
- Updated fillable attributes in Question model to remove exam_id

### Factories
- Created InstitutionFactory
- Created ExamFactory
- Created ExplanationCacheFactory
- Updated LearningInsightFactory with all required fields

### Tests
- Created ExamQuestionTest to test the many-to-many relationship
- Updated ExplanationCacheTest to use standardized difficulty levels
- Created LearningInsightTest
- Added tests for relationships between models

## Status
- All unit tests for the refactored components are passing
- Maintained compatibility with existing code while improving the structure

## Next Steps
- Continue updating remaining tests to use PHPUnit attributes instead of doc-comments
- Review and consolidate other redundant migrations if needed
- Run full test suite to ensure no regressions
