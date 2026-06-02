---
layout: page
aside: false
---

<script setup>
import { onMounted } from 'vue';
import { useRouter, withBase } from 'vitepress';

const router = useRouter();
const target = withBase('/browser/');

onMounted(() => {
  router.go(target);
});
</script>

<meta http-equiv="refresh" :content="`0; url=${target}`">

# Redirecting

Continue to [Browser](/browser/).
