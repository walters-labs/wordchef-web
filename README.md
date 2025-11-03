# wordchef

PHP + pgSQL + pgvector webapp to mix words using vector embeddings and display images of vector embeddings.

- Uses spaCy to generate full vocab and corresponding wordvectors
- Word embedding vectors are stored in a PostgreSQL database with pgvector which allows fast semantic search
- Given two words, look up their wordcvectors and take the average
- Find the nearest five words to the averaged vector
