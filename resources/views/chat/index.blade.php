@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4" x-data="chatApp({{ auth()->user()->id }})">
    <div class="grid grid-cols-3 gap-4">
        <!-- Sidebar -->
        <div class="border p-2">
            <input type="text" placeholder="Search users..." class="w-full p-2 border mb-2"
                   x-model="searchQuery" @input="searchUsers">

            <template x-for="user in searchResults" :key="user.id">
                <div class="p-2 border-b hover:bg-gray-100 cursor-pointer" @click="startConversation(user)">
                    <span x-text="user.name"></span>
                </div>
            </template>

            <h2 class="font-bold mt-4">Your Conversations</h2>
            <template x-for="conv in conversations" :key="conv.id">
                <div class="p-2 border-b hover:bg-gray-100 cursor-pointer"
                     @click="loadConversation(conv)">
                    <span x-text="getOtherUserName(conv)"></span>
                </div>
            </template>
        </div>

        <!-- Chat Window -->
        <div class="col-span-2 border flex flex-col h-[500px]">
            <div class="flex-1 overflow-y-auto p-4 space-y-2" id="chat-box">
                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.sender_id === userId ? 'text-right' : 'text-left'">
                        <div class="inline-block bg-gray-200 p-2 rounded" x-text="msg.message"></div>
                    </div>
                </template>
            </div>

            <form @submit.prevent="sendMessage" class="p-4 border-t flex">
                <input type="text" class="flex-1 border p-2" x-model="newMessage" placeholder="Type a message...">
                <button class="ml-2 px-4 py-2 bg-blue-500 text-white rounded">Send</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>

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

        searchUsers() {
            if (this.searchQuery.length === 0) {
                this.searchResults = [];
                return;
            }

            axios.get('/chat/search?q=' + this.searchQuery)
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
                });
        },

        loadConversation(conv) {
            this.currentConversation = conv;
            axios.get('/chat/messages/' + conv.id)
                .then(res => {
                    this.messages = res.data;
                    this.scrollToBottom();
                });
        },

        sendMessage() {
            if (this.newMessage === '' || !this.currentConversation) return;

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
            setTimeout(() => {
                let chatBox = document.getElementById('chat-box');
                chatBox.scrollTop = chatBox.scrollHeight;
            }, 100);
        },

        listen() {
            Echo.private(`chat.${this.userId}`)
                .listen('MessageSent', (e) => {
                    if (this.currentConversation && e.sender.id === this.currentConversation.user_one_id || e.sender.id === this.currentConversation.user_two_id) {
                        this.messages.push({
                            sender_id: e.sender.id,
                            message: e.message
                        });
                        this.scrollToBottom();
                    }
                });
        },

        init() {
            this.listen();
        }
    }
}
</script>
@endpush
