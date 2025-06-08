@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4" x-data="chatApp({{ auth()->user()->id }})" x-init="init">
    <div class="grid grid-cols-3 gap-4 h-[600px]">
        <!-- Sidebar -->
        <div class="border p-4 overflow-y-auto">
            <!-- Search Input -->
            <input type="text" placeholder="Search users..." class="w-full p-2 border mb-4 rounded"
                   x-model="searchQuery" @input="searchUsers">

            <!-- Search Results -->
            <template x-if="searchResults.length > 0">
                <div>
                    <h3 class="font-semibold text-gray-600 mb-2">Search Results</h3>
                    <template x-for="user in searchResults" :key="user.id">
                        <div class="p-2 hover:bg-gray-100 cursor-pointer rounded mb-1"
                             @click="startConversation(user)">
                            <span x-text="user.name"></span>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Conversations -->
            <h3 class="font-semibold text-gray-600 mt-4 mb-2">Conversations</h3>
            <template x-for="conv in conversations" :key="conv.id">
                <div class="p-2 hover:bg-blue-100 cursor-pointer rounded mb-1"
                     :class="{ 'bg-blue-200': currentConversation?.id === conv.id }"
                     @click="loadConversation(conv)">
                    <span x-text="getOtherUserName(conv)"></span>
                </div>
            </template>
        </div>

        <!-- Chat Window -->
        <div class="col-span-2 border flex flex-col rounded">
            <!-- Messages -->
            <div class="flex-1 overflow-y-auto p-4 space-y-2 bg-gray-50" id="chat-box">
                <template x-if="!currentConversation">
                    <div class="text-center text-gray-500 mt-20">Select or start a conversation</div>
                </template>

                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.sender_id === userId ? 'text-right' : 'text-left'">
                        <div class="inline-block px-3 py-2 rounded shadow"
                             :class="msg.sender_id === userId ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800'">
                            <span x-text="msg.message"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Input -->
            <form @submit.prevent="sendMessage" class="p-4 border-t flex items-center gap-2 bg-white"
                  x-show="currentConversation">
                <input type="text" class="flex-1 border p-2 rounded" x-model="newMessage"
                       placeholder="Type a message..." @keydown.enter="sendMessage">
                <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Send</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- AlpineJS + Axios + Pusher -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script> <!-- Ensure Echo is included in app.js -->

<script>
function chatApp(userId) {
    return {
        userId,
        searchQuery: '',
        searchResults: [],
        conversations: @json($conversations),
        currentConversation: null,
        messages: [],
        newMessage: '',

        init() {
            this.listen();
        },

        searchUsers() {
            if (this.searchQuery.trim() === '') {
                this.searchResults = [];
                return;
            }

            axios.get(`/chat/search?q=${this.searchQuery}`)
                .then(res => {
                    this.searchResults = res.data;
                });
        },

        getOtherUserName(conv) {
            return conv.user_one_id === this.userId ? conv.user_two.name : conv.user_one.name;
        },

        startConversation(user) {
            axios.post('/chat/start', { user_id: user.id })
                .then(res => {
                    let existing = this.conversations.find(c => c.id === res.data.id);
                    if (!existing) {
                        this.conversations.unshift(res.data);
                    }
                    this.loadConversation(res.data);
                    this.searchQuery = '';
                    this.searchResults = [];
                });
        },

        loadConversation(conv) {
            this.currentConversation = conv;
            axios.get(`/chat/messages/${conv.id}`)
                .then(res => {
                    this.messages = res.data;
                    this.scrollToBottom();
                });
        },

        sendMessage() {
            if (!this.newMessage.trim()) return;
            axios.post('/chat/send', {
                conversation_id: this.currentConversation.id,
                message: this.newMessage
            }).then(res => {
                this.messages.push(res.data);
                this.newMessage = '';
                this.scrollToBottom();
            });
        },

        scrollToBottom() {
            this.$nextTick(() => {
                let box = document.getElementById('chat-box');
                if (box) box.scrollTop = box.scrollHeight;
            });
        },

        listen() {
            if (!window.Echo) return;

            Echo.private(`chat.${this.userId}`)
                .listen('MessageSent', (e) => {
                    if (this.currentConversation &&
                        (e.sender.id === this.currentConversation.user_one_id || e.sender.id === this.currentConversation.user_two_id)) {
                        this.messages.push({
                            sender_id: e.sender.id,
                            message: e.message
                        });
                        this.scrollToBottom();
                    }
                });
        }
    }
}
</script>
@endpush
